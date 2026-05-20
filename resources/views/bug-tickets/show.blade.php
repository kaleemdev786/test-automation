<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #{{ $ticket->id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Syntax highlighting for code blocks --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script>hljs.highlightAll();</script>
</head>
<body class="bg-gray-100 min-h-screen">

<div class="max-w-5xl mx-auto px-4 py-8">

    {{-- Back + header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('bug-tickets.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← All Tickets</a>
            <h1 class="text-xl font-bold text-gray-800">Ticket #{{ $ticket->id }} — {{ $ticket->title }}</h1>
        </div>

        @php
            $statusColors = [
                'pending'    => 'bg-yellow-100 text-yellow-700',
                'approved'   => 'bg-blue-100 text-blue-700',
                'processing' => 'bg-purple-100 text-purple-700',
                'fixed'      => 'bg-green-100 text-green-700',
                'failed'     => 'bg-red-100 text-red-700',
            ];
        @endphp
        <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $statusColors[$ticket->status] ?? '' }}">
            {{ ucfirst($ticket->status) }}
        </span>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-4 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Meta card --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div>
            <p class="text-gray-400 text-xs uppercase font-medium">Module</p>
            <p class="font-semibold text-gray-700">{{ $ticket->module }}</p>
        </div>
        <div>
            <p class="text-gray-400 text-xs uppercase font-medium">Priority</p>
            @php
                $priorityColors = [
                    'critical' => 'text-red-600',
                    'high'     => 'text-orange-600',
                    'medium'   => 'text-blue-600',
                    'low'      => 'text-gray-600',
                ];
            @endphp
            <p class="font-semibold {{ $priorityColors[$ticket->priority] ?? '' }}">{{ ucfirst($ticket->priority) }}</p>
        </div>
        <div>
            <p class="text-gray-400 text-xs uppercase font-medium">Laravel</p>
            <p class="font-semibold text-gray-700">{{ $ticket->laravel_version }}</p>
        </div>
        <div>
            <p class="text-gray-400 text-xs uppercase font-medium">Submitted</p>
            <p class="font-semibold text-gray-700">{{ $ticket->created_at->format('d M Y H:i') }}</p>
        </div>
    </div>

    @if($ticket->description)
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <h2 class="font-semibold text-gray-700 mb-1 text-sm uppercase tracking-wide">Description</h2>
        <p class="text-gray-600 text-sm">{{ $ticket->description }}</p>
    </div>
    @endif

    {{-- Screenshot + Approve --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Screenshot</h2>
            @if($ticket->isPending())
                <form method="POST" action="{{ route('bug-tickets.approve', $ticket) }}">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('Approve this ticket and trigger the AI fix?')"
                            class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                        ✅ Approve & Run AI Fix
                    </button>
                </form>
            @endif
        </div>
        <img src="{{ route('bug-tickets.image', $ticket) }}"
             alt="Bug screenshot"
             class="rounded-lg border border-gray-200 max-w-full">
    </div>

    {{-- Processing spinner --}}
    @if($ticket->isProcessing())
    <div class="bg-purple-50 border border-purple-200 rounded-xl p-6 mb-6 flex items-center gap-4">
        <svg class="animate-spin h-6 w-6 text-purple-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
        </svg>
        <div>
            <p class="font-semibold text-purple-700">AI is analysing the screenshot…</p>
            <p class="text-sm text-purple-500">This usually takes 10–30 seconds. Refresh the page to check progress.</p>
        </div>
    </div>
    @endif

    {{-- Failed --}}
    @if($ticket->isFailed())
    <div class="bg-red-50 border border-red-200 rounded-xl p-5 mb-6">
        <h2 class="font-semibold text-red-700 mb-1">❌ AI Fix Failed</h2>
        <p class="text-sm text-red-600">{{ $ticket->error_message }}</p>
    </div>
    @endif

    {{-- ── AI Fix Result ─────────────────────────────────────────────────────── --}}
    @if($ticket->isFixed() && $ticket->fix_result)
    @php $fix = $ticket->fix_result; @endphp

    {{-- Image analysis --}}
    <div class="bg-white rounded-xl shadow p-5 mb-4">
        <h2 class="font-bold text-gray-700 mb-3 flex items-center gap-2">
            <span class="text-lg">🔍</span> Image Analysis
        </h2>
        <div class="space-y-2 text-sm text-gray-700">
            <p><span class="font-medium text-gray-500">What Claude sees:</span> {{ $fix['image_analysis']['what_i_see'] ?? '—' }}</p>
            <p><span class="font-medium text-gray-500">Highlighted problem:</span> {{ $fix['image_analysis']['highlighted_problem'] ?? '—' }}</p>
            <p><span class="font-medium text-gray-500">Error type:</span>
                <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs font-semibold">
                    {{ $fix['image_analysis']['error_type'] ?? '—' }}
                </span>
            </p>
        </div>
    </div>

    {{-- Analysis --}}
    <div class="bg-white rounded-xl shadow p-5 mb-4">
        <h2 class="font-bold text-gray-700 mb-3 flex items-center gap-2">
            <span class="text-lg">🧠</span> Analysis
        </h2>
        <div class="space-y-2 text-sm text-gray-700">
            <p><span class="font-medium text-gray-500">Root cause:</span> {{ $fix['analysis']['root_cause'] ?? '—' }}</p>
            <p><span class="font-medium text-gray-500">Impact:</span> {{ $fix['analysis']['impact'] ?? '—' }}</p>
            <p><span class="font-medium text-gray-500">Fix strategy:</span> {{ $fix['analysis']['fix_strategy'] ?? '—' }}</p>
            @if(!empty($fix['analysis']['files_to_change']))
            <div>
                <span class="font-medium text-gray-500">Files to change:</span>
                <ul class="mt-1 space-y-1">
                    @foreach($fix['analysis']['files_to_change'] as $file)
                        <li class="font-mono text-xs bg-gray-100 px-2 py-1 rounded inline-block">{{ $file }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>

    {{-- Code fix --}}
    @if(!empty($fix['code_fix']))
    <div class="bg-white rounded-xl shadow p-5 mb-4">
        <h2 class="font-bold text-gray-700 mb-1 flex items-center gap-2">
            <span class="text-lg">🔧</span> Code Fix
        </h2>
        <p class="text-xs text-gray-400 font-mono mb-3">{{ $fix['code_fix']['file'] ?? '' }}</p>

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <p class="text-xs font-semibold text-red-600 uppercase mb-1">Before (buggy)</p>
                <pre class="rounded-lg overflow-auto text-xs max-h-80"><code class="language-php">{{ $fix['code_fix']['before'] ?? '' }}</code></pre>
            </div>
            <div>
                <p class="text-xs font-semibold text-green-600 uppercase mb-1">After (fixed)</p>
                <pre class="rounded-lg overflow-auto text-xs max-h-80"><code class="language-php">{{ $fix['code_fix']['after'] ?? '' }}</code></pre>
            </div>
        </div>
    </div>
    @endif

    {{-- Git Suggested Info (from Claude) --}}
    @if(!empty($fix['git']))
    <div class="bg-white rounded-xl shadow p-5 mb-4">
        <h2 class="font-bold text-gray-700 mb-3 flex items-center gap-2">
            <span class="text-lg">🌿</span> Git — Suggested by AI
        </h2>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
                <p class="text-gray-400 text-xs">Branch</p>
                <code class="bg-gray-100 px-2 py-0.5 rounded text-xs">{{ $fix['git']['branch'] ?? '—' }}</code>
            </div>
            <div>
                <p class="text-gray-400 text-xs">Commit message</p>
                <code class="bg-gray-100 px-2 py-0.5 rounded text-xs">{{ $fix['git']['commit'] ?? '—' }}</code>
            </div>
            <div class="col-span-2">
                <p class="text-gray-400 text-xs">PR Title</p>
                <p class="font-medium text-gray-700">{{ $fix['git']['pr_title'] ?? '—' }}</p>
            </div>
            <div class="col-span-2">
                <p class="text-gray-400 text-xs">PR Body</p>
                <p class="text-gray-600 text-sm">{{ $fix['git']['pr_body'] ?? '—' }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Git Automation Result (what actually happened) --}}
    @if(!empty($fix['git_result']))
    @php $gr = $fix['git_result']; @endphp
    <div class="bg-white rounded-xl shadow p-5 mb-4 border-l-4 {{ $gr['merged'] ? 'border-green-500' : ($gr['pushed'] ? 'border-blue-400' : 'border-yellow-400') }}">
        <h2 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
            <span class="text-lg">⚙️</span> Git — What Actually Happened
        </h2>

        {{-- Status badges --}}
        <div class="flex flex-wrap gap-2 mb-4">
            <span class="px-2 py-1 rounded text-xs font-semibold {{ $gr['file_patched'] ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                {{ $gr['file_patched'] ? '✅ File patched' : '⚠️ Fix notes committed' }}
            </span>
            <span class="px-2 py-1 rounded text-xs font-semibold {{ $gr['pushed'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                {{ $gr['pushed'] ? '✅ Branch pushed' : '❌ Push failed' }}
            </span>
            <span class="px-2 py-1 rounded text-xs font-semibold {{ $gr['merged'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                {{ $gr['merged'] ? '✅ Merged → ' . $gr['base_branch'] : '— Not merged' }}
            </span>
        </div>

        {{-- Key details --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm mb-4">
            <div>
                <p class="text-gray-400 text-xs">Branch created</p>
                <code class="bg-gray-100 px-2 py-0.5 rounded text-xs">{{ $gr['branch'] ?? '—' }}</code>
            </div>
            <div>
                <p class="text-gray-400 text-xs">Commit hash</p>
                <code class="bg-gray-100 px-2 py-0.5 rounded text-xs">{{ $gr['commit'] ?? '—' }}</code>
            </div>
            <div>
                <p class="text-gray-400 text-xs">Base branch</p>
                <code class="bg-gray-100 px-2 py-0.5 rounded text-xs">{{ $gr['base_branch'] ?? 'main' }}</code>
            </div>
            @if(!empty($gr['patched_file']))
            <div>
                <p class="text-gray-400 text-xs">File patched</p>
                <code class="bg-gray-100 px-2 py-0.5 rounded text-xs break-all">{{ $gr['patched_file'] }}</code>
            </div>
            @endif
        </div>

        {{-- Git command log --}}
        @if(!empty($gr['log']))
        <details class="mt-2">
            <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-600">Show git log</summary>
            <pre class="mt-2 bg-gray-900 text-green-400 text-xs rounded-lg p-3 overflow-auto max-h-48">{{ implode("\n---\n", array_filter($gr['log'])) }}</pre>
        </details>
        @endif
    </div>
    @endif

    {{-- Deploy checklist --}}
    @if(!empty($fix['deploy']))
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <h2 class="font-bold text-gray-700 mb-3 flex items-center gap-2">
            <span class="text-lg">🚀</span> Deploy Checklist
        </h2>
        <div class="flex flex-wrap gap-3 text-sm mb-3">
            @php
                $checks = [
                    'run_migration' => 'php artisan migrate',
                    'run_composer'  => 'composer install',
                    'restart_queue' => 'php artisan queue:restart',
                ];
            @endphp
            @foreach($checks as $key => $label)
                <span class="flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium
                    {{ $fix['deploy'][$key] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400 line-through' }}">
                    {{ $fix['deploy'][$key] ? '✓' : '✗' }} {{ $label }}
                </span>
            @endforeach
        </div>
        @if(!empty($fix['deploy']['notes']))
        <p class="text-sm text-gray-600 bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2">
            📝 {{ $fix['deploy']['notes'] }}
        </p>
        @endif
    </div>
    @endif

    @endif {{-- end isFixed --}}

</div>

</body>
</html>
