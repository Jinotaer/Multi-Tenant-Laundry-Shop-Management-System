<x-tenant-layout>
    @php
        $showCreateTicketModal = $errors->isNotEmpty() && old('form_context') === 'support-ticket-create';
    @endphp

    <div class="space-y-4">
        <x-tenant-header title="Customer Support" description="Submit and track your support tickets." />

        @if (session('success'))
            <div class="rounded-lg bg-green-50 p-4 text-sm text-green-700 border border-green-200 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
                {{ session('success') }}
            </div>
        @endif

        <div class="flex justify-between items-center">
            <p class="text-sm text-gray-600 dark:text-slate-400">Get help from our support team</p>
            <button
                type="button"
                x-data
                x-on:click="$dispatch('open-modal', 'support-ticket-create-modal')"
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

    <x-modal name="support-ticket-create-modal" :show="$showCreateTicketModal" maxWidth="2xl" focusable>
        <form method="POST" action="{{ route('tenant.support.store') }}" class="p-6">
            @csrf
            <input type="hidden" name="form_context" value="support-ticket-create">

            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Create Support Ticket</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Send a message to the support team and track replies.</p>
                </div>
                <button type="button" x-on:click="$dispatch('close')" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mt-6 space-y-4 max-h-[70vh] overflow-y-auto pr-1">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Subject <span class="text-red-500">*</span></label>
                    <input type="text" name="subject" value="{{ old('subject') }}" required
                        class="block w-full rounded-xl {{ $errors->has('subject') ? 'border-red-300' : 'border-gray-300 dark:border-slate-700' }} dark:bg-slate-800 dark:text-slate-100 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Category</label>
                    <select name="category" class="block w-full rounded-xl border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="general" {{ old('category', 'general') === 'general' ? 'selected' : '' }}>General</option>
                        <option value="technical" {{ old('category') === 'technical' ? 'selected' : '' }}>Technical Issue</option>
                        <option value="billing" {{ old('category') === 'billing' ? 'selected' : '' }}>Billing</option>
                        <option value="feature" {{ old('category') === 'feature' ? 'selected' : '' }}>Feature Request</option>
                        <option value="account" {{ old('category') === 'account' ? 'selected' : '' }}>Account</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Priority</label>
                    <select name="priority" class="block w-full rounded-xl border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="normal" {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="priority" {{ old('priority') === 'priority' ? 'selected' : '' }}>High Priority</option>
                        <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Message <span class="text-red-500">*</span></label>
                    <textarea name="message" rows="6" required
                        class="block w-full rounded-xl {{ $errors->has('message') ? 'border-red-300' : 'border-gray-300 dark:border-slate-700' }} dark:bg-slate-800 dark:text-slate-100 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('message') }}</textarea>
                    @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 border-t border-gray-200 dark:border-slate-800 pt-5 sm:flex-row sm:justify-end">
                <button type="button" x-on:click="$dispatch('close')"
                    class="inline-flex items-center justify-center rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-slate-300 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-800">
                    Cancel
                </button>
                <button type="submit" class="tenant-primary-action inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-medium text-white shadow-sm">
                    Create Ticket
                </button>
            </div>
        </form>
    </x-modal>
</x-tenant-layout>
