# AI Fix Notes — fix-the-desgin-bug-2026-05-20

**Ticket:** #6 — Fix the desgin bug

## Analysis
The Blade view for the bug tickets create page is using a centered container (likely a Bootstrap 'container' class or a Tailwind 'max-w-*' with 'mx-auto') that restricts the form to a narrow centered column, rather than using a full-width layout.

## Fix Strategy
Change the wrapping container in the Blade view from a fixed/centered container (e.g. 'container', 'max-w-2xl mx-auto', or similar) to a full-width container (e.g. 'container-fluid' for Bootstrap, or remove 'max-w-*' and use 'w-full' for Tailwind). Also update the card/form wrapper to be full width.

## File to Change
`resources/views/bug-tickets/create.blade.php`

## Before
```php
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
```

## After
```php
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
```
