<x-tenant-layout>
    <x-slot name="header">
        <x-tenant-header title="My Orders" subtitle="Track your active laundry orders and recent history." />
    </x-slot>

    @php
        $theme = tenant()->getThemePreset();
        $readyCount = $activeOrders->where('status', 'ready')->count();
        $inProgressCount = $activeOrders->count() - $readyCount;
    @endphp

    <div class="tenant-page-stack space-y-6">
        @if (!$customer)
            <div class="bg-yellow-50 border border-yellow-300 rounded-2xl p-8 text-center dark:bg-yellow-900/20 dark:border-yellow-700">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/40">
                    <svg class="h-7 w-7 text-yellow-500 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <p class="mt-4 text-base font-semibold text-yellow-900 dark:text-yellow-100">No customer profile found</p>
                <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">Your account email doesn't match any customer records. Please contact the shop.</p>
            </div>
        @else
            {{-- Summary strip --}}
            <div class="rounded-3xl p-5 sm:p-6 text-white shadow-lg relative overflow-hidden {{ $theme['primary_bg'] }}">
                <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
                <div class="relative flex flex-wrap items-center gap-6">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/80">Active Orders</p>
                        <p class="mt-1 text-3xl font-bold">{{ $activeOrders->count() }}</p>
                    </div>
                    <div class="h-10 w-px bg-white/20" aria-hidden="true"></div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/80">Ready Now</p>
                        <p class="mt-1 text-3xl font-bold">{{ $readyCount }}</p>
                    </div>
                    <div class="h-10 w-px bg-white/20" aria-hidden="true"></div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/80">In Progress</p>
                        <p class="mt-1 text-3xl font-bold">{{ $inProgressCount }}</p>
                    </div>
                </div>
            </div>

            {{-- Active Orders --}}
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-300">Active Orders</h3>
                        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Orders currently in the workflow.</p>
                    </div>
                </div>

                @if ($activeOrders->isEmpty())
                    <div class="bg-white rounded-2xl shadow-sm p-10 text-center ring-1 ring-gray-100 dark:bg-slate-800 dark:ring-slate-700">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-slate-700">
                            <svg class="h-7 w-7 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="mt-4 text-sm font-medium text-gray-700 dark:text-slate-300">No active orders</p>
                        <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">When the shop places a new order for you it will show up here.</p>
                        <a href="{{ route('tenant.dashboard') }}" class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                            Go to dashboard
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($activeOrders as $order)
                            @php $isReady = $order->status === 'ready'; @endphp
                            <div class="group relative bg-white rounded-2xl shadow-sm p-5 ring-1 ring-gray-100 hover:shadow-md hover:-translate-y-0.5 transition-all dark:bg-slate-800 dark:ring-slate-700">
                                @if ($isReady)
                                    <span class="absolute -top-2 -right-2 inline-flex items-center gap-1 rounded-full bg-emerald-500 px-2.5 py-0.5 text-[10px] font-semibold text-white shadow-md">
                                        <span class="h-1.5 w-1.5 rounded-full bg-white animate-pulse"></span>Ready
                                    </span>
                                @endif

                                <a href="{{ route('tenant.portal.show', $order) }}" class="block">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-slate-100">{{ $order->order_number }}</span>
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-medium {{ $order->status_color }}">
                                            {{ $order->status_label }}
                                        </span>
                                    </div>

                                    @if ($order->service)
                                        <p class="mt-3 text-sm text-gray-700 dark:text-slate-300 font-medium">{{ $order->service->name }}</p>
                                    @endif

                                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-slate-400">
                                        @if ($order->weight)
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m0-18l-4 4m4-4l4 4M3 12h18" /></svg>
                                                {{ $order->weight }} kg
                                            </span>
                                        @endif
                                        @if ($order->due_date)
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                                                {{ $order->due_date->format('M d') }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-slate-700 flex items-center justify-between">
                                        <span class="text-lg font-bold text-gray-900 dark:text-slate-100">₱{{ number_format($order->total_amount, 2) }}</span>
                                        <span class="inline-flex items-center gap-1 text-xs font-medium {{ $order->isPaid() ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500 dark:text-rose-400' }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $order->isPaid() ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                            {{ $order->isPaid() ? 'Paid' : 'Unpaid' }}
                                        </span>
                                    </div>
                                </a>

                                @if (!$order->isPaid() && tenant()->hasFeature('online_payments') && tenant()->paymongo_secret_key && $order->canBePaidOnline())
                                    <form method="POST" action="{{ route('tenant.order-payments.checkout', $order) }}" class="mt-3">
                                        @csrf
                                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-3 py-2 text-sm font-semibold text-white shadow-sm focus:outline-none focus:ring-2 {{ $theme['focus_ring'] }}">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                            </svg>
                                            Pay Online
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-tenant-layout>
