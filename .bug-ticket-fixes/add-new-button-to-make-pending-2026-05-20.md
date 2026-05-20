# AI Fix Notes — add-new-button-to-make-pending-2026-05-20

**Ticket:** #7 — Add new button to make pending

## Analysis
1) The Blade view is using a constrained container (like 'container' or 'max-w-*') instead of 'container-fluid' or 'w-full' to make it full width. 2) The actions column only conditionally shows 'Approve' for pending tickets but has no 'Pending' button for fixed/approved tickets.

## Fix Strategy
1) Update the Blade view to use full-width layout by replacing container with container-fluid or w-full. 2) Add a 'Pending' button/route/controller method to allow reverting ticket status back to pending. 3) Add the route, controller method, and blade button for the new 'Make Pending' action.

## File to Change
`resources/views/bug-tickets/index.blade.php`

## Before
```php
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🐛 Bug Tickets</h2>
        <a href="{{ route('bug-tickets.create') }}" class="btn btn-primary">+ New Ticket</a>
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>TITLE</th>
                        <th>MODULE</th>
                        <th>PRIORITY</th>
                        <th>LARAVEL</th>
                        <th>STATUS</th>
                        <th>CREATED</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tickets as $ticket)
                    <tr>
                        <td>{{ $ticket->id }}</td>
                        <td>{{ $ticket->title }}</td>
                        <td>{{ $ticket->module }}</td>
                        <td><span class="badge bg-danger">{{ $ticket->priority }}</span></td>
                        <td>{{ $ticket->laravel_version }}</td>
                        <td>
                            <span class="badge {{ $ticket->status === 'Fixed' ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ $ticket->status }}
                            </span>
                        </td>
                        <td>{{ $ticket->created_at->diffForHumans() }}</td>
                        <td>
                            <a href="{{ route('bug-tickets.show', $ticket) }}" class="text-primary">View</a>
                            @if($ticket->status === 'Pending')
                                <a href="{{ route('bug-tickets.approve', $ticket) }}" class="text-success">Approve</a>
                            @endif
                            <a href="{{ route('bug-tickets.destroy', $ticket) }}" class="text-danger"
                               onclick="event.preventDefault(); document.getElementById('delete-{{ $ticket->id }}').submit();">Delete</a>
                            <form id="delete-{{ $ticket->id }}" action="{{ route('bug-tickets.destroy', $ticket) }}" method="POST" style="display:none;">
                                @csrf @method('DELETE')
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
```

## After
```php
<div class="container-fluid px-4 py-4" style="width: 100%;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🐛 Bug Tickets</h2>
        <a href="{{ route('bug-tickets.create') }}" class="btn btn-primary">+ New Ticket</a>
    </div>
    <div class="card w-100">
        <div class="card-body">
            <table class="table table-hover w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>TITLE</th>
                        <th>MODULE</th>
                        <th>PRIORITY</th>
                        <th>LARAVEL</th>
                        <th>STATUS</th>
                        <th>CREATED</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tickets as $ticket)
                    <tr>
                        <td>{{ $ticket->id }}</td>
                        <td>{{ $ticket->title }}</td>
                        <td>{{ $ticket->module }}</td>
                        <td>
                            <span class="badge bg-danger">{{ $ticket->priority }}</span>
                        </td>
                        <td>{{ $ticket->laravel_version }}</td>
                        <td>
                            <span class="badge
                                @if($ticket->status === 'Fixed') bg-success
                                @elseif($ticket->status === 'Approved') bg-info
                                @else bg-warning text-dark
                                @endif">
                                {{ $ticket->status }}
                            </span>
                        </td>
                        <td>{{ $ticket->created_at->diffForHumans() }}</td>
                        <td>
                            <a href="{{ route('bug-tickets.show', $ticket) }}" class="text-primary me-1">View</a>

                            @if($ticket->status === 'Pending')
                                <form action="{{ route('bug-tickets.approve', $ticket) }}" method="POST" style="display:inline;">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-link text-success p-0 me-1">Approve</button>
                                </form>
                            @endif

                            @if(in_array($ticket->status, ['Approved', 'Fixed']))
                                <form action="{{ route('bug-tickets.pending', $ticket) }}" method="POST" style="display:inline;">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-link text-warning p-0 me-1">Pending</button>
                                </form>
                            @endif

                            <form id="delete-{{ $ticket->id }}" action="{{ route('bug-tickets.destroy', $ticket) }}" method="POST" style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-link text-danger p-0"
                                    onclick="return confirm('Are you sure you want to delete this ticket?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
```
