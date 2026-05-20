# AI Fix Notes — fix-the-desddgin-bug-2026-05-20

**Ticket:** #6 — Fix the desddgin bug

## Analysis
The Blade view for the bug ticket create page is using a centered, max-width constrained container (likely Tailwind classes such as 'max-w-xl', 'max-w-2xl', or 'container mx-auto') that limits the form to the center of the screen instead of stretching it to full screen width.

## Fix Strategy
Update the Blade view for the bug ticket create page to remove the max-width constraint and centering classes, replacing them with full-width layout classes so the form spans the entire screen width.

## File to Change
`resources/views/bug-tickets/create.blade.php`

## Before
```php
<div class="min-h-screen bg-gray-100 py-8">
    <div class="max-w-2xl mx-auto px-4">
        <div class="mb-4">
            <a href="{{ url()->previous() }}" class="text-gray-500 text-sm">&larr; Back</a>
            <h1 class="text-2xl font-bold inline ml-2">New Bug Ticket</h1>
        </div>
        <div class="bg-white rounded-lg shadow p-8">
            <!-- form content -->
        </div>
    </div>
</div>
```

## After
```php
<div class="min-h-screen bg-gray-100 py-8">
    <div class="w-full px-4">
        <div class="mb-4">
            <a href="{{ url()->previous() }}" class="text-gray-500 text-sm">&larr; Back</a>
            <h1 class="text-2xl font-bold inline ml-2">New Bug Ticket</h1>
        </div>
        <div class="bg-white rounded-lg shadow p-8 w-full">
            <!-- form content -->
        </div>
    </div>
</div>
```
