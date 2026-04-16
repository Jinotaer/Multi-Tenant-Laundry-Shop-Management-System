<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-slate-100 leading-tight">Customer Support</h2>
    </x-slot>

    <div class="space-y-4">
        @if (session('success'))
            <div class="rounded-lg bg-green-50 p-4 text-sm text-green-700 border border-green-200 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
                {{ session('success') }}
            </div>
        @endif

        <div class="flex justify-between items-center">
            <p class="text-sm text-gray-600 dark:text-slate-400">Get help from our support team</p>
            <button
                type="button"
                onclick="document.getElementById('createTicketModal').classList.remove('hidden')"
                class="tenant-primary-action inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium text-white shadow-sm"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New Ticket
            </button>
        </div>

        <div class="bg-white dark:bg-slate-900 shadow-sm sm:rounded-lg overflow-hidden">
            @if ($tickets->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="h-12 w-12 text-gray-300 dark:text-slate-600 mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                    </svg>
                    <p class="text-gray-500 dark:text-slate-400 text-sm font-medium">No support tickets yet</p>
                    <p class="text-gray-400 dark:text-slate-500 text-xs mt-1">Create a ticket to get help from our team</p>
                </div>
            @else
                <div class="divide-y divide-gray-200 dark:divide-slate-800">
                    @foreach ($tickets as $ticket)
                        <a href="{{ route('tenant.support.show', $ticket) }}" class="block hover:bg-gray-50 dark:hover:bg-slate-800 transition p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 truncate">
                                            {{ $ticket->subject }}
                                        </h3>
                                        @if ($ticket->unread_tenant_count > 0)
                                            <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full">
                                                {{ $ticket->unread_tenant_count }}
                                            </span>
                                        @endif
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
                                        @if ($ticket->sla_breached)
                                            <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-900/30 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-400">
                                                SLA Breached
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-slate-400 line-clamp-2">{{ $ticket->message }}</p>
                                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500 dark:text-slate-500">
                                        <span>{{ $ticket->created_at->diffForHumans() }}</span>
                                        @if ($ticket->messages_count > 0)
                                            <span class="flex items-center gap-1">
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                                                </svg>
                                                {{ $ticket->messages_count }} {{ Str::plural('message', $ticket->messages_count) }}
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

    <!-- Create Ticket Modal -->
    <div id="createTicketModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 dark:bg-slate-950 bg-opacity-75 dark:bg-opacity-75 transition-opacity" onclick="document.getElementById('createTicketModal').classList.add('hidden')"></div>

            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form method="POST" action="{{ route('tenant.support.store') }}">
                    @csrf
                    <div class="bg-white dark:bg-slate-900 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">Create Support Ticket</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Subject <span class="text-red-500">*</span></label>
                                <input type="text" name="subject" required
                                    class="block w-full rounded-md border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Category</label>
                                <select name="category" class="block w-full rounded-md border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="general">General</option>
                                    <option value="technical">Technical Issue</option>
                                    <option value="billing">Billing</option>
                                    <option value="feature">Feature Request</option>
                                    <option value="account">Account</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Priority</label>
                                <select name="priority" class="block w-full rounded-md border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="normal">Normal</option>
                                    <option value="priority">High Priority</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Message <span class="text-red-500">*</span></label>
                                <textarea name="message" rows="5" required
                                    class="block w-full rounded-md border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-slate-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button type="submit" class="tenant-primary-action w-full inline-flex justify-center rounded-md px-4 py-2 text-sm font-medium text-white shadow-sm sm:w-auto">
                            Create Ticket
                        </button>
                        <button type="button" onclick="document.getElementById('createTicketModal').classList.add('hidden')"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-medium text-gray-700 dark:text-slate-300 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-800 sm:mt-0 sm:w-auto">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-tenant-layout>
