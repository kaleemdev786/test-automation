# AI Fix Notes — fix/bug-tickets-fullscreen-layout

**Ticket:** #6 — Fix the desgin bug

## Analysis
The Blade view for the bug ticket create page is using a centered container (likely `container`, `max-w-2xl`, `mx-auto`, or similar Tailwind/Bootstrap classes) that restricts the form to a narrow centered card instead of filling the full screen width.

## Fix Strategy
Update the Blade view template for the bug ticket create page to remove the max-width constraint and centered container, replacing it with a full-width layout. Change the wrapping div classes from something like `max-w-2xl mx-auto` to `w-full` or remove the container constraint so the form spans the full screen.

## File to Change
`resources/views/bug-tickets/create.blade.php`

## Before
```php
<div class="container mx-auto max-w-2xl py-10 px-4">
    <div class="bg-white rounded-xl shadow p-8">
        <!-- form content -->
    </div>
</div>
```

## After
```php
<div class="w-full min-h-screen py-10 px-6">
    <div class="bg-white rounded-xl shadow p-8 w-full">
        <!-- form content -->
    </div>
</div>
```
