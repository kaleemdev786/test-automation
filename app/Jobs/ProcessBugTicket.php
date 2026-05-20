<?php

namespace App\Jobs;

use App\Models\BugTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ProcessBugTicket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 180;

    public function __construct(public BugTicket $ticket) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Main entry point
    // ─────────────────────────────────────────────────────────────────────────

    public function handle(): void
    {
        $this->ticket->update(['status' => BugTicket::STATUS_PROCESSING]);

        try {
            // 1. Ask Claude for the fix
            $fix = $this->callClaudeApi();

            // 2. Apply code change + git flow
            $gitResult = $this->applyGitFix($fix);

            // 3. Merge the git result into the fix payload so the UI can show it
            $fix['git_result'] = $gitResult;

            $this->ticket->update([
                'status'     => BugTicket::STATUS_FIXED,
                'fix_result' => $fix,
            ]);

            Log::info('BugTicket fixed successfully', [
                'ticket_id' => $this->ticket->id,
                'branch'    => $gitResult['branch'] ?? null,
            ]);

        } catch (\Throwable $e) {
            Log::error('BugTicket processing failed', [
                'ticket_id' => $this->ticket->id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            $this->ticket->update([
                'status'        => BugTicket::STATUS_FAILED,
                'error_message' => $e->getMessage() . ' [' . class_basename($e) . ']',
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Claude API
    // ─────────────────────────────────────────────────────────────────────────

    private function callClaudeApi(): array
    {
        $imagePath = $this->resolveImagePath();
        $imageData = base64_encode(file_get_contents($imagePath));
        $mediaType = $this->detectMediaType($this->ticket->image_path);

        Log::info('BugTicket: calling Claude API', [
            'ticket_id'   => $this->ticket->id,
            'image_path'  => $imagePath,
            'api_key_set' => ! empty(config('services.anthropic.key')),
        ]);

        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'Content-Type'      => 'application/json',
        ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-sonnet-4-6',
            'max_tokens' => 4096,
            'system'     => $this->buildSystemPrompt(),
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'   => 'image',
                            'source' => [
                                'type'       => 'base64',
                                'media_type' => $mediaType,
                                'data'       => $imageData,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => $this->buildUserPrompt(),
                        ],
                    ],
                ],
            ],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Anthropic API error ' . $response->status() . ': ' . $response->body()
            );
        }

        $raw = $response->json('content.0.text');

        if (! $raw) {
            throw new \RuntimeException('Empty response from Claude API. Full body: ' . $response->body());
        }

        // Strip markdown code fences if Claude wraps the JSON
        $raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $raw = preg_replace('/\s*```$/', '', $raw);

        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Claude returned invalid JSON: ' . json_last_error_msg() . '. Raw: ' . substr($raw, 0, 500));
        }

        return $decoded;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Git automation
    // ─────────────────────────────────────────────────────────────────────────

    private function applyGitFix(array $fix): array
    {
        $repoPath   = rtrim(config('bug-tickets.git.repo_path'), '/\\');
        $remote     = config('bug-tickets.git.remote', 'origin');
        $baseBranch = config('bug-tickets.git.base_branch', 'main');
        $autoMerge  = config('bug-tickets.git.auto_merge', true);

        // Sanitise branch name from Claude's suggestion
        $branch = $this->sanitiseBranchName(
            $fix['git']['branch'] ?? 'fix/ticket-' . $this->ticket->id
        );

        $commitMsg = $fix['git']['commit']   ?? 'fix: auto-fix by Bug Ticket Bot';
        $filePath  = $fix['code_fix']['file'] ?? null;
        $before    = $fix['code_fix']['before'] ?? null;
        $after     = $fix['code_fix']['after']  ?? null;

        $result = [
            'branch'        => $branch,
            'base_branch'   => $baseBranch,
            'commit'        => null,
            'pushed'        => false,
            'merged'        => false,
            'file_patched'  => false,
            'skipped_patch' => false,
            'log'           => [],
        ];

        // ── Configure git identity ──────────────────────────────────────────
        $authorName  = config('bug-tickets.git.author_name',  'Bug Ticket Bot');
        $authorEmail = config('bug-tickets.git.author_email', 'bot@bug-tickets.local');
        $this->git($repoPath, ['config', 'user.name',  $authorName]);
        $this->git($repoPath, ['config', 'user.email', $authorEmail]);

        // ── Set token-based remote URL if configured ────────────────────────
        $this->configureRemoteAuth($repoPath, $remote);

        // ── Make sure we are on the base branch and up to date ──────────────
        $checkoutBase = $this->git($repoPath, ['checkout', $baseBranch]);
        $result['log'][] = $checkoutBase['output'];

        // Only pull if the remote branch exists (safe on empty/new repos)
        $remoteCheck = $this->git($repoPath, ['ls-remote', '--heads', $remote, $baseBranch], failOk: true);
        if (! empty(trim($remoteCheck['output']))) {
            $pull = $this->git($repoPath, ['pull', $remote, $baseBranch], failOk: true);
            $result['log'][] = $pull['output'];
        } else {
            $result['log'][] = "Remote $baseBranch not found — skipping pull (fresh repo).";
        }

        // ── Create fix branch ───────────────────────────────────────────────
        $this->git($repoPath, ['branch', '-D', $branch], failOk: true); // clean up previous attempt
        $createBranch = $this->git($repoPath, ['checkout', '-b', $branch]);
        $result['log'][] = $createBranch['output'];

        // ── Apply the code fix ──────────────────────────────────────────────
        if ($filePath && $before && $after) {
            $absoluteFile = $repoPath . DIRECTORY_SEPARATOR . ltrim(
                str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath),
                DIRECTORY_SEPARATOR
            );

            if (file_exists($absoluteFile)) {
                $content = file_get_contents($absoluteFile);

                if (str_contains($content, $before)) {
                    file_put_contents($absoluteFile, str_replace($before, $after, $content));
                    $result['file_patched'] = true;
                    $result['patched_file'] = $filePath;
                    $result['log'][] = "✅ Patched: $filePath";
                } else {
                    $result['skipped_patch'] = true;
                    $result['log'][] = "⚠️ Before-block not found verbatim in $filePath — committed fix notes instead.";
                    $this->writeFixNoteFile($repoPath, $branch, $fix);
                }
            } else {
                $result['skipped_patch'] = true;
                $result['log'][] = "⚠️ File not found: $absoluteFile — committed fix notes instead.";
                $this->writeFixNoteFile($repoPath, $branch, $fix);
            }
        } else {
            $result['skipped_patch'] = true;
            $result['log'][] = "ℹ️ No code_fix from Claude — committed fix notes instead.";
            $this->writeFixNoteFile($repoPath, $branch, $fix);
        }

        // ── Commit ──────────────────────────────────────────────────────────
        $this->git($repoPath, ['add', '-A']);
        $commit = $this->git($repoPath, ['commit', '-m', $commitMsg], failOk: true);
        $result['log'][] = $commit['output'];

        // Capture commit hash
        $hash = $this->git($repoPath, ['rev-parse', '--short', 'HEAD'], failOk: true);
        $result['commit'] = trim($hash['output']);

        // ── Push fix branch ─────────────────────────────────────────────────
        $push = $this->git($repoPath, ['push', '-u', $remote, $branch], failOk: true);
        $result['log'][]  = $push['output'];
        $result['pushed'] = $push['success']; // ✅ use exit code, not string matching

        // ── Merge into base branch ──────────────────────────────────────────
        if ($autoMerge) {
            $checkoutMain = $this->git($repoPath, ['checkout', $baseBranch]);
            $result['log'][] = $checkoutMain['output'];

            $merge = $this->git($repoPath, ['merge', '--no-ff', $branch, '-m',
                "Merge branch '$branch' — auto-fix #" . $this->ticket->id
            ], failOk: true);
            $result['log'][]  = $merge['output'];
            $result['merged'] = $merge['success']; // ✅ use exit code

            if ($result['merged']) {
                $pushMain = $this->git($repoPath, ['push', $remote, $baseBranch], failOk: true);
                $result['log'][] = $pushMain['output'];
            }
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Git helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function git(string $repoPath, array $args, bool $failOk = false): array
    {
        $process = new Process(array_merge(['git'], $args), $repoPath);
        $process->setTimeout(60);
        $process->run();

        $output = trim($process->getOutput() . $process->getErrorOutput());

        if (! $process->isSuccessful() && ! $failOk) {
            throw new \RuntimeException(
                'Git command failed: git ' . implode(' ', $args) . "\n" . $output
            );
        }

        Log::debug('git ' . implode(' ', $args), ['output' => $output]);

        return [
            'success' => $process->isSuccessful(),
            'output'  => $output ?: '(no output)',
        ];
    }

    private function configureRemoteAuth(string $repoPath, string $remote): void
    {
        $token      = config('bug-tickets.git.token');
        $username   = config('bug-tickets.git.username');
        $remoteUrl  = config('bug-tickets.git.remote_url');

        if ($token && $remoteUrl) {
            // Inject credentials into remote URL: https://user:token@github.com/...
            $authenticated = preg_replace(
                '#^https://#',
                'https://' . urlencode($username) . ':' . urlencode($token) . '@',
                $remoteUrl
            );
            $this->git($repoPath, ['remote', 'set-url', $remote, $authenticated], failOk: true);
            Log::info('BugTicket: remote URL configured with token auth');
        }
    }

    private function writeFixNoteFile(string $repoPath, string $branch, array $fix): void
    {
        $notesDir  = $repoPath . DIRECTORY_SEPARATOR . '.bug-ticket-fixes';
        // Replace / with - so "fix/my-bug" becomes a safe filename "fix-my-bug.md"
        $safeFilename = str_replace('/', '-', $branch);
        $notesFile = $notesDir . DIRECTORY_SEPARATOR . $safeFilename . '.md';

        if (! is_dir($notesDir)) {
            mkdir($notesDir, 0755, true);
        }

        $content  = "# AI Fix Notes — {$branch}\n\n";
        $content .= "**Ticket:** #{$this->ticket->id} — {$this->ticket->title}\n\n";
        $content .= "## Analysis\n" . ($fix['analysis']['root_cause'] ?? 'N/A') . "\n\n";
        $content .= "## Fix Strategy\n" . ($fix['analysis']['fix_strategy'] ?? 'N/A') . "\n\n";
        $content .= "## File to Change\n`" . ($fix['code_fix']['file'] ?? 'unknown') . "`\n\n";
        $content .= "## Before\n```php\n" . ($fix['code_fix']['before'] ?? '') . "\n```\n\n";
        $content .= "## After\n```php\n" . ($fix['code_fix']['after'] ?? '') . "\n```\n";

        file_put_contents($notesFile, $content);
    }

    private function sanitiseBranchName(string $name): string
    {
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9\-\/]/', '-', $name);
        $name = preg_replace('/-+/', '-', $name);
        return trim($name, '-/');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Image helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function resolveImagePath(): string
    {
        $candidates = [
            storage_path('app/public/' . $this->ticket->image_path),
            storage_path('app/' . $this->ticket->image_path),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        throw new \RuntimeException(
            'Screenshot not found. Tried: ' . implode(', ', $candidates)
        );
    }

    private function detectMediaType(string $path): string
    {
        return match(strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            default       => 'image/png',
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prompt builders
    // ─────────────────────────────────────────────────────────────────────────

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a Laravel expert AI agent responsible for automatically fixing bugs in a Laravel application.
The user will provide a screenshot where the problematic area is highlighted or marked.

Your job is to:
- Look at the image carefully
- Understand what the highlighted/marked area is showing
- Identify the bug from the visual context
- Write the exact Laravel PHP fix for it

Rules:
- Only fix what is visible in the highlighted area
- Do not touch unrelated code
- Follow Laravel best practices (Eloquent, Facades, Form Requests, etc)
- Write clean, production-ready PHP code
- If the fix requires a migration, include it
- If the fix requires a route change, include it

Respond ONLY in this JSON format with no extra text or markdown:

{
  "image_analysis": {
    "what_i_see": "Describe what you see in the screenshot",
    "highlighted_problem": "Describe specifically what the highlighted area is showing",
    "error_type": "e.g. SQL error, validation error, null reference, etc"
  },
  "analysis": {
    "root_cause": "Explain what is causing the bug",
    "impact": "Explain who is affected and how",
    "fix_strategy": "Explain how you will fix it",
    "files_to_change": ["list of file paths that need changes"]
  },
  "code_fix": {
    "file": "path/to/file.php",
    "before": "paste the buggy code block here",
    "after": "paste the fixed code block here"
  },
  "git": {
    "branch": "fix/short-branch-name",
    "commit": "fix: short commit message",
    "pr_title": "fix: descriptive pull request title",
    "pr_body": "Short description of what was changed and why"
  },
  "deploy": {
    "run_migration": true,
    "run_composer": false,
    "restart_queue": false,
    "notes": "Any extra deployment notes"
  }
}
PROMPT;
    }

    private function buildUserPrompt(): string
    {
        return <<<PROMPT
Bug Ticket:

Title:           {$this->ticket->title}
Module:          {$this->ticket->module}
Priority:        {$this->ticket->priority}
Laravel Version: {$this->ticket->laravel_version}
Description:     {$this->ticket->description}

I have attached a screenshot where the problematic area is highlighted.
Please look at the image, understand the issue from the highlighted section,
and return the full fix in the required JSON format.
PROMPT;
    }
}
