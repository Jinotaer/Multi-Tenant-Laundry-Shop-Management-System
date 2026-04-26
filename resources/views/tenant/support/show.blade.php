<x-tenant-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('tenant.support.index') }}" class="text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-slate-100 leading-tight">{{ $ticket->subject }}</h2>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Ticket #{{ $ticket->id }}</p>
            </div>
        </div>
    </x-slot>

    <div class="tenant-page-stack max-w-4xl mx-auto">
        @if (session('success'))
            <div class="rounded-lg bg-green-50 p-4 text-sm text-green-700 border border-green-300 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400 mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white dark:bg-slate-900 shadow-sm sm:rounded-lg overflow-hidden">
            <!-- Ticket Header -->
            <div class="border-b border-gray-200 dark:border-slate-800 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            @if ($ticket->status === 'open')
                                <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:text-green-400">
                                    Open
                                </span>
                            @elseif ($ticket->status === 'in_progress')
                                <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/30 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-400">
                                    In Progress
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-slate-700 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:text-slate-300">
                                    Closed
                                </span>
                            @endif
                            <span class="text-xs text-gray-500 dark:text-slate-400">
                                Created {{ $ticket->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div class="px-6 py-4 space-y-4 max-h-[600px] overflow-y-auto">
                @forelse ($ticket->messages as $message)
                    <div class="flex {{ $message->isAdminMessage() ? 'justify-start' : 'justify-end' }}">
                        <div class="max-w-[75%]">
                            <div class="flex items-center gap-2 mb-1 {{ $message->isAdminMessage() ? '' : 'justify-end' }}">
                                <span class="text-xs font-medium {{ $message->isAdminMessage() ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-slate-300' }}">
                                    {{ $message->isAdminMessage() ? 'Support Team' : 'You' }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-slate-500">
                                    {{ $message->created_at->format('M d, Y g:i A') }}
                                </span>
                            </div>
                            <div class="rounded-lg px-4 py-2.5 {{ $message->isAdminMessage() ? 'bg-gray-100 dark:bg-slate-800 text-gray-900 dark:text-slate-100' : 'bg-indigo-600 text-white' }}">
                                <p class="text-sm whitespace-pre-wrap">{{ $message->message }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <p class="text-sm text-gray-500 dark:text-slate-400">No messages yet</p>
                    </div>
                @endforelse
            </div>

            <!-- Message Input -->
            @if ($ticket->status !== 'closed')
                <div class="border-t border-gray-200 dark:border-slate-800 px-6 py-4">
                    <form method="POST" action="{{ route('tenant.support.message', $ticket) }}" class="flex gap-3">
                        @csrf
                        <textarea
                            name="message"
                            rows="2"
                            required
                            placeholder="Type your message..."
                            class="flex-1 rounded-md border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-sm text-sm resize-none"
                        ></textarea>
                        <button type="submit" class="tenant-primary-action self-end rounded-md px-4 py-2 text-sm font-medium text-white shadow-sm">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                        </button>
                    </form>
                </div>
            @else
                <div class="border-t border-gray-200 dark:border-slate-800 px-6 py-4 bg-gray-50 dark:bg-slate-800">
                    <p class="text-sm text-gray-600 dark:text-slate-400 text-center">
                        This ticket has been closed. Create a new ticket if you need further assistance.
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-tenant-layout>
