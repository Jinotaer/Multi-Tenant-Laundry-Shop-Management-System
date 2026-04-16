<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-slate-100 leading-tight">Dashboard</h2>
    </x-slot>

    @php $theme = tenant()->getThemePreset(); @endphp

    <div class="space-y-6">
        @if (!$customer)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center dark:bg-yellow-900/20 dark:border-yellow-700">
                <svg class="mx-auto h-12 w-12 text-yellow-400 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">No customer profile found</p>
                <p class="text-xs text-yellow-600 dark:text-yellow-300 mt-1">Your account email doesn't match any customer records. Please contact the shop.</p>
            </div>
        @else
            {{-- Welcome Section --}}
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl shadow-lg p-6 text-white">
                <h3 class="text-2xl font-bold">Welcome back, {{ $customer->name }}!</h3>
                <p class="mt-2 text-indigo-100">Here's your laundry overview</p>
            </div>

            {{-- Stats Overview --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl shadow-sm p-5 dark:bg-slate-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-slate-400">Active Orders</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-slate-100">{{ $activeOrders->count() }}</p>
                        </div>
                        <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900/30">
                            <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-5 dark:bg-slate-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-slate-400">Total Orders</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-slate-100">{{ $totalOrders }}</p>
                        </div>
                        <div class="rounded-full bg-green-100 p-3 dark:bg-green-900/30">
                            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                            </svg>
                        </div>
                    </div>
                </div>

                @if (tenant()->hasFeature('customer_loyalty') && $loyalty)
                    <div class="bg-white rounded-2xl shadow-sm p-5 dark:bg-slate-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-slate-400">Loyalty Points</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($loyalty->points) }}</p>
                            </div>
                            <div class="rounded-full bg-amber-100 p-3 dark:bg-amber-900/30">
                                <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm p-5 dark:bg-slate-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-slate-400">Current Tier</p>
                                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-slate-100">{{ ucfirst($loyalty->tier) }}</p>
                            </div>
                            <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900/30">
                                <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0" />
                                </svg>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-white rounded-2xl shadow-sm p-5 dark:bg-slate-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-slate-400">Total Spent</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-slate-100">₱{{ number_format($totalSpent, 2) }}</p>
                            </div>
                            <div class="rounded-full bg-emerald-100 p-3 dark:bg-emerald-900/30">
                                <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Loyalty Rewards Section --}}
            @if (tenant()->hasFeature('customer_loyalty') && $loyalty)
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-300 mb-3">Loyalty Rewards</h3>
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm lg:col-span-2 dark:border-amber-700 dark:bg-amber-900/20">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700 dark:text-amber-400">Loyalty Tier</p>
                                    <h3 class="mt-2 text-xl font-semibold text-gray-900 dark:text-slate-100">{{ ucfirst($loyalty->tier) }} Tier</h3>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-slate-400">{{ \App\Models\CustomerLoyalty::tierLabels()[$loyalty->tier] ?? ucfirst($loyalty->tier) }}</p>
                                </div>
                                <div class="rounded-2xl bg-white px-4 py-3 text-right shadow-sm dark:bg-slate-800">
                                    <p class="text-xs text-gray-500 dark:text-slate-400">Reward Value</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-slate-100">₱{{ number_format($loyalty->getRewardValue(), 2) }}</p>
                                </div>
                            </div>

                            @if ($loyalty->nextTier())
                                <div class="mt-5">
                                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-slate-400">
                                        <span>Progress to {{ ucfirst($loyalty->nextTier()) }}</span>
                                        <span>{{ $loyalty->progressToNextTier() }}%</span>
                                    </div>
                                    <div class="mt-2 h-2 rounded-full bg-white dark:bg-slate-700">
                                        <div class="h-2 rounded-full bg-amber-500" style="width: {{ $loyalty->progressToNextTier() }}%"></div>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500 dark:text-slate-400">Spend ₱{{ number_format($loyalty->spendingNeededForNextTier(), 2) }} more to reach {{ ucfirst($loyalty->nextTier()) }}.</p>
                                </div>
                            @else
                                <p class="mt-5 text-xs font-medium text-amber-700 dark:text-amber-400">Top tier unlocked. You are receiving the highest loyalty multiplier.</p>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-slate-400">Points</p>
                            <p class="mt-3 text-3xl font-semibold text-gray-900 dark:text-slate-100">{{ number_format($loyalty->points) }}</p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">Earned from completed orders.</p>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-slate-400">Stamps</p>
                            <p class="mt-3 text-3xl font-semibold text-gray-900 dark:text-slate-100">{{ number_format($loyalty->stamps) }}</p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">One stamp per order.</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Order History --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-300">Order History</h3>
                    <form method="GET" class="flex items-center gap-2">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search order #..."
                            class="rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 text-sm px-3 py-1.5 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:focus:border-indigo-400">
                    </form>
                </div>

                <div class="bg-white shadow-sm rounded-2xl overflow-hidden dark:bg-slate-800">
                    @if ($orderHistory->isEmpty())
                        <div class="p-8 text-center">
                            <p class="text-gray-400 dark:text-slate-500 text-sm">No orders found.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                                <thead class="bg-gray-50 dark:bg-slate-900">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Order #</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Service</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Total</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Payment</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                    @foreach ($orderHistory as $order)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-750">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="{{ route('tenant.portal.show', $order) }}" class="font-medium text-gray-900 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400">
                                                    {{ $order->order_number }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">{{ $order->service?->name ?? '—' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $order->status_color }}">{{ $order->status_label }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-slate-300">₱{{ number_format($order->total_amount, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm {{ $order->isPaid() ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                                                {{ $order->isPaid() ? 'Paid' : 'Unpaid' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 dark:text-slate-500">{{ $order->created_at->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($orderHistory->hasPages())
                            <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700">{{ $orderHistory->links() }}</div>
                        @endif
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-tenant-layout>
