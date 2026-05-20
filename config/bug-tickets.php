<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Git Repository Settings
    |--------------------------------------------------------------------------
    |
    | Absolute path to the Laravel project git repo that the AI will patch.
    | This can be the same project or a different one on disk.
    |
    */

    'git' => [

        // Absolute path to the root of the git repository to patch
        // e.g. C:\Users\Kaleem\Sites\my-laravel-app
        'repo_path' => env('BUG_TICKET_REPO_PATH', base_path()),

        // Git remote name (almost always 'origin')
        'remote' => env('BUG_TICKET_GIT_REMOTE', 'origin'),

        // Base branch to merge fixes into (e.g. 'main' or 'master')
        'base_branch' => env('BUG_TICKET_BASE_BRANCH', 'main'),

        // GitHub/GitLab personal access token for pushing over HTTPS.
        // Leave blank if you use SSH keys (git push will work without this).
        'token' => env('BUG_TICKET_GIT_TOKEN', ''),

        // GitHub/GitLab username (needed when using token auth over HTTPS)
        'username' => env('BUG_TICKET_GIT_USERNAME', ''),

        // Remote HTTPS URL without credentials
        // e.g. https://github.com/your-org/your-repo.git
        // Leave blank to use whatever remote is already configured in the repo.
        'remote_url' => env('BUG_TICKET_GIT_REMOTE_URL', ''),

        // Automatically merge the fix branch into base_branch after push?
        'auto_merge' => env('BUG_TICKET_AUTO_MERGE', true),

        // Git author identity for commits
        'author_name'  => env('BUG_TICKET_GIT_AUTHOR_NAME', 'Bug Ticket Bot'),
        'author_email' => env('BUG_TICKET_GIT_AUTHOR_EMAIL', 'bot@bug-tickets.local'),
    ],

];
