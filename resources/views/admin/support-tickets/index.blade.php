<x-admin-layout>
    <x-slot name="header">
        <x-admin-header title="Support Tickets" description="Manage shop support inquiries." />
    </x-slot>

    <div class="space-y-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.support-tickets.index') }}" class="rounded-md px-3 py-1.5 text-sm font-medium {{ !request('status') ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300' }}">
                All
            </a>
            <a href="{{ route('admin.support-tickets.index', ['status' => 'open']) }}" class="rounded-md px-3 py-1.5 text-sm font-medium {{ request('status') === 'open' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300' }}">
                Open
            </a>
            <a href="{{ route('admin.support-tickets.index', ['status' => 'in_progress']) }}" class="rounded-md px-3 py-1.5 text-sm font-medium {{ request('status') === 'in_progress' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300' }}">
                In Progress
            </a>
            <a href="{{ route('admin.support-tickets.index', ['status' => 'closed']) }}" class="rounded-md px-3 py-1.5 text-sm font-medium {{ request('status') === 'closed' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300' }}">
                Closed
            </a>
        </div>

        <div class="bg-white dark:bg-slate-900 shadow-sm sm:rounded-lg overflow-hidden">
            @if ($tickets->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="h-12 w-12 text-gray-300 dark:text-slate-600 mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                    </svg>
                    <p class="text-gray-500 dark:text-slate-400 text-sm font-medium">No support tickets</p>
                </div>
            @else
                <div class="divide-y divide-gray-200 dark:divide-slate-800">
                    @foreach ($tickets as $ticket)
                        <a href="{{ route('admin.support-tickets.show', $ticket) }}" class="block hover:bg-gray-50 dark:hover:bg-slate-800 transition p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 truncate">
                                            {{ $ticket->subject }}
                                        </h3>
                                        @if ($ticket->status === 'open')
                                            <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/30 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400">
                                                Open
                                            </span>
                                        @elseif ($ticket->status === 'in_progress')
                                            <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/30 px-2 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-400">
                                                In Progress
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-slate-700 px-2 py-0.5 text-xs font-medium text-gray-700 dark:text-slate-300">
                                                Closed
                                            </span>
                                        @endif
                                        @if ($ticket->priority === 'priority')
                                            <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-900/30 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-400">
                                                High Priority
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-slate-400 line-clamp-2">{{ $ticket->message }}</p>
                                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500 dark:text-slate-500">
                                        <span class="font-medium">{{ $ticket->tenant->data['shop_name'] ?? $ticket->tenant_id }}</span>
                                        <span>{{ $ticket->created_at->diffForHumans() }}</span>
                                        @if ($ticket->messages_count > 0)
                                            <span class="flex items-center gap-1">
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                                                </svg>
                                                {{ $ticket->messages_count }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <svg class="h-5 w-5 text-gray-400 dark:text-slate-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>

                @if ($tickets->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800">
                        {{ $tickets->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-admin-layout>
