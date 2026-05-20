<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessBugTicket;
use App\Models\BugTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BugTicketController extends Controller
{
    /**
     * List all tickets, newest first.
     */
    public function index(): View
    {
        $tickets = BugTicket::latest()->paginate(20);

        return view('bug-tickets.index', compact('tickets'));
    }

    /**
     * Show the create ticket form.
     */
    public function create(): View
    {
        return view('bug-tickets.create');
    }

    /**
     * Store a new ticket (status = pending by default).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'           => 'required|string|max:255',
            'module'          => 'required|string|max:100',
            'priority'        => 'required|in:low,medium,high,critical',
            'laravel_version' => 'required|string|max:20',
            'description'     => 'nullable|string|max:2000',
            'screenshot'      => 'required|image|mimes:png,jpg,jpeg,gif,webp|max:10240',
        ]);

        // Store screenshot on the public disk → publicly accessible via storage/ URL
        $path = $request->file('screenshot')->store('bug-tickets', 'public');

        BugTicket::create([
            'title'           => $validated['title'],
            'module'          => $validated['module'],
            'priority'        => $validated['priority'],
            'laravel_version' => $validated['laravel_version'],
            'description'     => $validated['description'] ?? null,
            'image_path'      => $path,
            'status'          => BugTicket::STATUS_PENDING,
        ]);

        return redirect()->route('bug-tickets.index')
            ->with('success', 'Ticket submitted successfully. Awaiting approval.');
    }

    /**
     * Show a single ticket with its AI-generated fix (if available).
     */
    public function show(BugTicket $bugTicket): View
    {
        return view('bug-tickets.show', ['ticket' => $bugTicket]);
    }

    /**
     * Approve a pending ticket and dispatch the AI fix job.
     */
    public function approve(Request $request, BugTicket $bugTicket): RedirectResponse
    {
        if (! $bugTicket->isPending()) {
            return back()->with('error', 'Only pending tickets can be approved.');
        }

        $bugTicket->update([
            'status'      => BugTicket::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Dispatch to queue — AI call happens in the background
        ProcessBugTicket::dispatch($bugTicket);

        return redirect()->route('bug-tickets.show', $bugTicket)
            ->with('success', 'Ticket approved. AI is analysing the screenshot…');
    }

    /**
     * Serve the ticket screenshot directly (no storage:link needed).
     */
    public function image(BugTicket $bugTicket): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // Try public disk first, then fall back to local disk (old uploads)
        $paths = [
            storage_path('app/public/' . $bugTicket->image_path),
            storage_path('app/' . $bugTicket->image_path),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return response()->file($path);
            }
        }

        abort(404, 'Image not found.');
    }

    /**
     * Delete a ticket (and its screenshot).
     */
    public function destroy(BugTicket $bugTicket): RedirectResponse
    {
        if (file_exists(storage_path('app/public/' . $bugTicket->image_path))) {
            unlink(storage_path('app/public/' . $bugTicket->image_path));
        }

        $bugTicket->delete();

        return redirect()->route('bug-tickets.index')
            ->with('success', 'Ticket deleted.');
    }
}
