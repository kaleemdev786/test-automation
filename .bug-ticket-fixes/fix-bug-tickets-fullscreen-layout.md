# AI Fix Notes — fix/bug-tickets-fullscreen-layout

**Ticket:** #5 — Fix bug

## Analysis
The Blade view for the bug ticket create page is using a centered container (likely a Bootstrap 'container' or a Tailwind 'max-w-*' class with 'mx-auto') that restricts the form width. It needs to use a full-width container instead.

## Fix Strategy
Update the Blade view to replace the centered/max-width container with a full-width layout. Change the wrapping div from a constrained centered container to a full-screen layout using w-full or container-fluid (Bootstrap) / removing max-w constraints (Tailwind).

## File to Change
`resources/views/bug-tickets/create.blade.php`

## Before
```php
<div class="min-h-screen bg-gray-100 flex items-center justify-center py-10">
    <div class="bg-white rounded-xl shadow p-8 w-full max-w-2xl">
        <div class="mb-6">
            <a href="{{ route('bug-tickets.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back</a>
            <h1 class="text-2xl font-bold text-gray-800 mt-1">New Bug Ticket</h1>
        </div>
        <!-- form fields -->
    </div>
</div>
```

## After
```php
<div class="min-h-screen bg-gray-100 py-10 px-6">
    <div class="bg-white rounded-xl shadow p-8 w-full">
        <div class="mb-6">
            <a href="{{ route('bug-tickets.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back</a>
            <h1 class="text-2xl font-bold text-gray-800 mt-1">New Bug Ticket</h1>
        </div>
        <!-- form fields -->
    </div>
</div>
```
