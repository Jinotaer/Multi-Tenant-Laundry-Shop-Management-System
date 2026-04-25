<x-tenant-layout>
    <x-slot name="header">
        <x-tenant-header title="Dashboard" subtitle="Welcome to your dashboard" />
    </x-slot>

    @php
        $theme = tenant()->getThemePreset();
        $readyCount = $activeOrders->where('status', 'ready')->count();
        $unpaidCount = $activeOrders->where('payment_status', '!=', 'paid')->count();
    @endphp

    <div class="space-y-6">
        @if (!$customer)
            <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-8 text-center dark:bg-yellow-900/20 dark:border-yellow-700">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/40">
                    <svg class="h-7 w-7 text-yellow-500 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <p class="mt-4 text-base font-semibold text-yellow-900 dark:text-yellow-100">No customer profile found</p>
                <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">Your account email doesn't match any customer records. Please contact the shop.</p>
            </div>
        @else
            {{-- Welcome hero --}}
            <div class="relative overflow-hidden rounded-3xl p-6 sm:p-8 text-white shadow-lg {{ $theme['primary_bg'] }}">
                <div class="absolute -right-10 -top-10 h-44 w-44 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
                <div class="absolute -bottom-16 -left-10 h-52 w-52 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/80">Welcome back</p>
                        <h3 class="mt-1 text-2xl sm:text-3xl font-bold">Hi, {{ $customer->name }}</h3>
                        <p class="mt-2 text-sm text-white/80 max-w-md">Track every order, view your loyalty rewards, and pay outstanding balances in one place.</p>
                    </div>
                    <a href="{{ route('tenant.portal.index') }}" class="inline-flex items-center gap-2 self-start sm:self-auto rounded-full bg-white/15 px-4 py-2 text-sm font-semibold text-white ring-1 ring-inset ring-white/20 hover:bg-white/25 transition">
                        View active orders
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                    </a>
                </div>
            </div>

            {{-- Stats Overview --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 p-5 dark:bg-slate-800 dark:ring-slate-700">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-slate-400">Active</p>
                            <p class="mt-2 text-2xl sm:text-3xl font-bold text-gray-900 dark:text-slate-100">{{ $activeOrders->count() }}</p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">
                                {{ $readyCount ? "{$readyCount} ready for pickup" : 'Orders in progress' }}
                            </p>
                        </div>
                        <div class="rounded-xl bg-blue-50 p-2.5 dark:bg-blue-900/30">
                            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 p-5 dark:bg-slate-800 dark:ring-slate-700">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-slate-400">Total Orders</p>
                            <p class="mt-2 text-2xl sm:text-3xl font-bold text-gray-900 dark:text-slate-100">{{ $totalOrders }}</p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">Placed with this shop</p>
                        </div>
                        <div class="rounded-xl bg-emerald-50 p-2.5 dark:bg-emerald-900/30">
                            <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                            </svg>
                        </div>
                    </div>
                </div>

                @if (tenant()->hasFeature('customer_loyalty') && $loyalty)
                    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 p-5 dark:bg-slate-800 dark:ring-slate-700">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-slate-400">Loyalty Points</p>
                                <p class="mt-2 text-2xl sm:text-3xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($loyalty->points) }}</p>
                                <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">≈ ₱{{ number_format($loyalty->getRewardValue(), 2) }} value</p>
                            </div>
                            <div class="rounded-xl bg-amber-50 p-2.5 dark:bg-amber-900/30">
                                <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 p-5 dark:bg-slate-800 dark:ring-slate-700">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-slate-400">Tier</p>
                                <p class="mt-2 text-xl sm:text-2xl font-bold text-gray-900 dark:text-slate-100">{{ ucfirst($loyalty->tier) }}</p>
                                <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">{{ \App\Models\CustomerLoyalty::tierLabels()[$loyalty->tier] ?? '—' }}</p>
                            </div>
                            <div class="rounded-xl bg-purple-50 p-2.5 dark:bg-purple-900/30">
                                <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0" />
                                </svg>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 p-5 dark:bg-slate-800 dark:ring-slate-700">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-slate-400">Total Spent</p>
                                <p class="mt-2 text-2xl sm:text-3xl font-bold text-gray-900 dark:text-slate-100">₱{{ number_format($totalSpent, 2) }}</p>
                                <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">Across all paid orders</p>
                            </div>
                            <div class="rounded-xl bg-emerald-50 p-2.5 dark:bg-emerald-900/30">
                                <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    @if ($unpaidCount > 0)
                        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 p-5 dark:bg-slate-800 dark:ring-slate-700">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-slate-400">Unpaid</p>
                                    <p class="mt-2 text-2xl sm:text-3xl font-bold text-rose-600 dark:text-rose-400">{{ $unpaidCount }}</p>
                                    <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">Awaiting payment</p>
                                </div>
                                <div class="rounded-xl bg-rose-50 p-2.5 dark:bg-rose-900/30">
                                    <svg class="h-5 w-5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 p-5 dark:bg-slate-800 dark:ring-slate-700">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-slate-400">All Settled</p>
                                    <p class="mt-2 text-2xl sm:text-3xl font-bold text-emerald-600 dark:text-emerald-400">✓</p>
                                    <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">No unpaid orders</p>
                                </div>
                                <div class="rounded-xl bg-emerald-50 p-2.5 dark:bg-emerald-900/30">
                                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            {{-- Loyalty Rewards Section --}}
            @if (tenant()->hasFeature('customer_loyalty') && $loyalty)
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-300 mb-3">Loyalty Rewards</h3>
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                        <div class="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-6 shadow-sm lg:col-span-2 dark:border-amber-700/60 dark:from-amber-900/20 dark:to-slate-800">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700 dark:text-amber-400">Loyalty Tier</p>
                                    <h3 class="mt-2 text-xl font-semibold text-gray-900 dark:text-slate-100">{{ ucfirst($loyalty->tier) }} Tier</h3>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-slate-400">{{ \App\Models\CustomerLoyalty::tierLabels()[$loyalty->tier] ?? ucfirst($loyalty->tier) }}</p>
                                </div>
                                <div class="rounded-2xl bg-white px-4 py-3 text-right shadow-sm ring-1 ring-amber-100 dark:bg-slate-800 dark:ring-slate-700">
                                    <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-slate-400">Reward Value</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-slate-100">₱{{ number_format($loyalty->getRewardValue(), 2) }}</p>
                                </div>
                            </div>

                            @if ($loyalty->nextTier())
                                <div class="mt-6">
                                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-slate-400">
                                        <span>Progress to {{ ucfirst($loyalty->nextTier()) }}</span>
                                        <span class="font-semibold text-amber-700 dark:text-amber-400">{{ $loyalty->progressToNextTier() }}%</span>
                                    </div>
                                    <div class="mt-2 h-2.5 rounded-full bg-white dark:bg-slate-700 overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-amber-600 transition-all" style="width: {{ $loyalty->progressToNextTier() }}%"></div>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500 dark:text-slate-400">Spend ₱{{ number_format($loyalty->spendingNeededForNextTier(), 2) }} more to reach {{ ucfirst($loyalty->nextTier()) }}.</p>
                                </div>
                            @else
                                <div class="mt-5 flex items-center gap-2 text-xs font-medium text-amber-700 dark:text-amber-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>
                                    Top tier unlocked — you earn the highest loyalty multiplier.
                                </div>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-slate-400">Points</p>
                            <p class="mt-3 text-3xl font-semibold text-gray-900 dark:text-slate-100">{{ number_format($loyalty->points) }}</p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">Earned from completed orders.</p>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-slate-400">Stamps</p>
                            <p class="mt-3 text-3xl font-semibold text-gray-900 dark:text-slate-100">{{ number_format($loyalty->stamps) }}</p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">One stamp per order.</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Order History --}}
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-300">Order History</h3>
                        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Every order you've placed with this shop.</p>
                    </div>
                    <form method="GET" class="flex items-center gap-2">
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M16.5 10.5a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search order #..."
                                class="rounded-full border-gray-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 text-sm pl-9 pr-3 py-1.5 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:focus:border-indigo-400">
                        </div>
                    </form>
                </div>

                <div class="bg-white shadow-sm rounded-2xl overflow-hidden ring-1 ring-gray-100 dark:bg-slate-800 dark:ring-slate-700">
                    @if ($orderHistory->isEmpty())
                        <div class="p-10 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-slate-700">
                                <svg class="h-7 w-7 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </div>
                            <p class="mt-4 text-sm font-medium text-gray-700 dark:text-slate-300">No orders yet</p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">When the shop places orders for you they'll appear here.</p>
                        </div>
                    @else
                        {{-- Desktop table --}}
                        <div class="hidden md:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-700">
                                <thead class="bg-gray-50 dark:bg-slate-900/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Order #</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Service</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-right text-[11px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Total</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Payment</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                    @foreach ($orderHistory as $order)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/40 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="{{ route('tenant.portal.show', $order) }}" class="font-medium text-gray-900 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400">
                                                    {{ $order->order_number }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">{{ $order->service?->name ?? '—' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $order->status_color }}">{{ $order->status_label }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-700 dark:text-slate-300">₱{{ number_format($order->total_amount, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @if ($order->isPaid())
                                                    <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Paid
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-rose-500 dark:text-rose-400 font-medium">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>Unpaid
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 dark:text-slate-500">{{ $order->created_at->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{-- Mobile cards --}}
                        <div class="md:hidden divide-y divide-gray-100 dark:divide-slate-700">
                            @foreach ($orderHistory as $order)
                                <a href="{{ route('tenant.portal.show', $order) }}" class="block p-4 hover:bg-gray-50 dark:hover:bg-slate-700/40 transition">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900 dark:text-slate-100">{{ $order->order_number }}</p>
                                            <p class="text-sm text-gray-500 dark:text-slate-400 truncate">{{ $order->service?->name ?? '—' }}</p>
                                            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">{{ $order->created_at->format('M d, Y') }}</p>
                                        </div>
                                        <div class="flex flex-col items-end gap-1.5 shrink-0">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $order->status_color }}">{{ $order->status_label }}</span>
                                            <span class="font-semibold text-gray-900 dark:text-slate-100">₱{{ number_format($order->total_amount, 2) }}</span>
                                            <span class="text-xs {{ $order->isPaid() ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500 dark:text-rose-400' }}">
                                                {{ $order->isPaid() ? 'Paid' : 'Unpaid' }}
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        @if ($orderHistory->hasPages())
                            <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-700">{{ $orderHistory->links() }}</div>
                        @endif
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-tenant-layout>
