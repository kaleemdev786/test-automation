<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bug Tickets</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

<div class="max-w-7xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">🐛 Bug Tickets</h1>
        <a href="{{ route('bug-tickets.create') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg transition">
            + New Ticket
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Module</th>
                    <th class="px-4 py-3">Priority</th>
                    <th class="px-4 py-3">Laravel</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Created</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tickets as $ticket)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 text-gray-400">{{ $ticket->id }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $ticket->title }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $ticket->module }}</td>
                    <td class="px-4 py-3">
                        @php
                            $priorityColors = [
                                'critical' => 'bg-red-100 text-red-700',
                                'high'     => 'bg-orange-100 text-orange-700',
                                'medium'   => 'bg-blue-100 text-blue-700',
                                'low'      => 'bg-gray-100 text-gray-600',
                            ];
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $priorityColors[$ticket->priority] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($ticket->priority) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $ticket->laravel_version }}</td>
                    <td class="px-4 py-3">
                        @php
                            $statusColors = [
                                'pending'    => 'bg-yellow-100 text-yellow-700',
                                'approved'   => 'bg-blue-100 text-blue-700',
                                'processing' => 'bg-purple-100 text-purple-700',
                                'fixed'      => 'bg-green-100 text-green-700',
                                'failed'     => 'bg-red-100 text-red-700',
                            ];
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusColors[$ticket->status] ?? '' }}">
                            {{ ucfirst($ticket->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $ticket->created_at->diffForHumans() }}</td>
                    <td class="px-4 py-3 flex items-center gap-2">
                        <a href="{{ route('bug-tickets.show', $ticket) }}"
                           class="text-blue-600 hover:underline text-xs font-medium">View</a>

                        @if($ticket->isPending())
                            <form method="POST" action="{{ route('bug-tickets.approve', $ticket) }}">
                                @csrf
                                <button type="submit"
                                        class="text-green-600 hover:underline text-xs font-medium"
                                        onclick="return confirm('Approve this ticket and run the AI fix?')">
                                    Approve
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('bug-tickets.destroy', $ticket) }}">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="text-red-400 hover:underline text-xs font-medium"
                                    onclick="return confirm('Delete this ticket?')">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-10 text-center text-gray-400">
                        No tickets yet. <a href="{{ route('bug-tickets.create') }}" class="text-blue-500 hover:underline">Create one</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $tickets->links() }}
    </div>
</div>

</body>
</html>
