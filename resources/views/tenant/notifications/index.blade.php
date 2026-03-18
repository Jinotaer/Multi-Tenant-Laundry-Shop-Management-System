<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Notifications</h2>
    </x-slot>

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Recent in-app alerts for order updates and loyalty activity.</p>
                <p class="mt-1 text-xs text-gray-400">{{ $unreadCount }} unread notification{{ $unreadCount === 1 ? '' : 's' }}</p>
            </div>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('tenant.notifications.mark-all-read') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                        Mark all as read
                    </button>
                </form>
            @endif
        </div>

        <div class="space-y-3">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $category = $data['category'] ?? 'general';
                    $categoryLabel = match ($category) {
                        'order_update' => 'Order Update',
                        'loyalty_reward' => 'Loyalty Reward',
                        default => 'Notification',
                    };
                @endphp

                <a
                    href="{{ $data['url'] ?? route('tenant.notifications.index') }}"
                    class="block rounded-2xl border {{ $notification->read_at ? 'border-gray-200 bg-white' : 'border-indigo-200 bg-indigo-50/50' }} p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $notification->read_at ? 'bg-gray-100 text-gray-600' : 'bg-indigo-100 text-indigo-700' }}">
                                    {{ $categoryLabel }}
                                </span>
                                @if (! $notification->read_at)
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
                                @endif
                            </div>
                            <p class="mt-3 text-sm font-semibold text-gray-900">{{ $data['title'] ?? 'Notification' }}</p>
                            <p class="mt-1 text-sm text-gray-600">{{ $data['body'] ?? '' }}</p>
                        </div>
                        <div class="text-right text-xs text-gray-400">
                            {{ $notification->created_at?->diffForHumans() }}
                        </div>
                    </div>
                </a>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center">
                    <p class="text-sm font-medium text-gray-700">No notifications yet.</p>
                    <p class="mt-1 text-xs text-gray-400">New order updates and loyalty alerts will appear here.</p>
                </div>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div>
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-tenant-layout>
