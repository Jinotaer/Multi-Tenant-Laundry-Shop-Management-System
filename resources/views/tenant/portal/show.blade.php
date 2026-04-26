<x-tenant-layout>
    <x-slot name="header">
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
        <x-tenant-header :title="$order->order_number" subtitle="View order status, items, and payment details.">
        </x-tenant-header>
    </x-slot>

    @php
        $theme = tenant()->getThemePreset();
        $steps = \App\Models\Order::statusLabelsForPlan();
        $stepKeys = array_keys($steps);
        $currentIndex = array_search($order->status, $stepKeys, true);
        $currentIndex = is_int($currentIndex) ? $currentIndex : -1;
        $totalSteps = count($stepKeys);
        $progressPercent = $totalSteps > 1 && $currentIndex >= 0
            ? ($currentIndex / ($totalSteps - 1)) * 100
            : 0;
        $itemsTotal = 0;
        if ($order->items) {
            foreach ($order->items as $item) {
                $itemsTotal += ((int) ($item['qty'] ?? 1)) * ((float) ($item['price'] ?? 0));
            }
        }
        
        $icons = [
            'received' => 'inventory_2',
            'in_progress' => 'local_laundry_service',
            'washing' => 'water_drop',
            'drying' => 'air',
            'folding' => 'dry_cleaning',
            'ready' => 'checkroom',
            'claimed' => 'task_alt',
        ];
    @endphp

    <div class="tenant-page-stack max-w-4xl mx-auto space-y-6">
        {{-- Hero summary --}}
        <div class="relative overflow-hidden rounded-3xl p-6 sm:p-7 text-white shadow-lg {{ $theme['primary_bg'] }}">
            <div class="absolute -right-12 -top-12 h-44 w-44 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
            <div class="absolute -bottom-16 -left-10 h-52 w-52 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/80">Order</p>
                    <h3 class="mt-1 text-2xl sm:text-3xl font-bold">{{ $order->order_number }}</h3>
                    @if ($order->service)
                        <p class="mt-1 text-sm text-white/85">{{ $order->service->name }}{{ $order->weight ? ' · ' . $order->weight . ' kg' : '' }}</p>
                    @endif
                </div>
                <div class="flex flex-col sm:items-end gap-1">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/80">Total</p>
                    <p class="text-3xl font-bold">₱{{ number_format($order->total_amount, 2) }}</p>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ring-white/20">
                        <span class="h-1.5 w-1.5 rounded-full {{ $order->isPaid() ? 'bg-emerald-300' : 'bg-amber-300 animate-pulse' }}"></span>
                        {{ $order->isPaid() ? 'Paid' : 'Unpaid' }}
                    </span>
                </div>
            </div>

            @if (!$order->isPaid() && tenant()->hasFeature('online_payments') && tenant()->paymongo_secret_key && $order->canBePaidOnline())
                <form method="POST" action="{{ route('tenant.order-payments.checkout', $order) }}" class="relative mt-5">
                    @csrf
                    <button type="submit" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 shadow-sm hover:bg-white/90 focus:outline-none focus:ring-2 focus:ring-white/70 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                        </svg>
                        Pay Online Now
                    </button>
                </form>
            @endif
        </div>

        {{-- Status Progress --}}
        <div class="bg-white shadow-sm rounded-2xl p-6 ring-1 ring-gray-100 dark:bg-slate-800 dark:ring-slate-700">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-300">Order Progress</h3>
                @if ($currentIndex >= 0)
                    <span class="text-xs text-gray-400 dark:text-slate-500">Step {{ $currentIndex + 1 }} of {{ $totalSteps }}</span>
                @endif
            </div>

            {{-- Desktop stepper --}}
            <div class="hidden sm:block">
                <div class="relative flex justify-between mt-8 mb-4 px-4">
                    {{-- Progress Line (Single Continuous Line) --}}
                    <div class="absolute -translate-y-1/2 h-1 bg-gray-200 dark:bg-slate-700" 
                         style="top: 20px; left: {{ 100 / ($totalSteps * 2) }}%; right: {{ 100 / ($totalSteps * 2) }}%;">
                        <div class="absolute left-0 top-0 bottom-0 transition-all duration-500" 
                             style="width: {{ $progressPercent }}%; background-color: var(--tenant-theme-accent);"></div>
                    </div>

                    @foreach ($steps as $key => $label)
                        @php
                            $stepIndex = array_search($key, $stepKeys, true);
                            $stepIndex = is_int($stepIndex) ? $stepIndex : -1;
                            $isComplete = $stepIndex < $currentIndex;
                            $isCurrent = $stepIndex === $currentIndex;
                        @endphp
                        <div class="relative flex flex-col items-center flex-1">
                            @if($isCurrent || $isComplete)
                                @if($isCurrent)
                                    <!-- Current Step (Has Gap) -->
                                    <div class="relative z-10 w-10 h-10 rounded-full bg-white dark:bg-slate-900 border-2 flex items-center justify-center ring-4 ring-white dark:ring-slate-800 shadow-sm transition-transform hover:scale-110" style="border-color: var(--tenant-theme-accent); color: var(--tenant-theme-accent);">
                                        <span class="material-symbols-outlined text-[20px]">{{ $icons[$key] ?? 'circle' }}</span>
                                    </div>
                                @else
                                    <!-- Completed Step (No Gap) -->
                                    <div class="relative z-10 w-10 h-10 rounded-full text-white flex items-center justify-center transition-transform hover:scale-110 ring-4 ring-white dark:ring-slate-800 shadow-sm" style="background-color: var(--tenant-theme-accent);">
                                        <span class="material-symbols-outlined text-[20px]">{{ $icons[$key] ?? 'circle' }}</span>
                                    </div>
                                @endif
                                <span class="mt-3 text-[11px] font-bold uppercase tracking-wider text-gray-900 dark:text-slate-100 text-center" style="color: var(--tenant-theme-accent);">{{ $label }}</span>
                            @else
                                <!-- Future Step (Has Gap) -->
                                <div class="relative z-10 w-10 h-10 rounded-full bg-gray-100 dark:bg-slate-800 text-gray-400 dark:text-slate-500 flex items-center justify-center ring-4 ring-white dark:ring-slate-800 transition-transform hover:scale-110">
                                    <span class="material-symbols-outlined text-[20px]">{{ $icons[$key] ?? 'circle' }}</span>
                                </div>
                                <span class="mt-3 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500 text-center">{{ $label }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Mobile stacked stepper --}}
            <ol class="sm:hidden space-y-4">
                @foreach ($steps as $key => $label)
                    @php
                        $stepIndex = array_search($key, $stepKeys, true);
                        $stepIndex = is_int($stepIndex) ? $stepIndex : -1;
                        $isComplete = $stepIndex < $currentIndex;
                        $isCurrent = $stepIndex === $currentIndex;
                    @endphp
                    <li class="flex items-start gap-3">
                        <div class="flex flex-col items-center">
                            <div class="relative z-10 flex h-7 w-7 items-center justify-center rounded-full text-[11px] font-bold shadow-sm
                                {{ $isCurrent || $isComplete ? 'text-white' : 'bg-gray-200 text-gray-400 dark:bg-slate-700 dark:text-slate-500' }}"
                                style="{{ $isCurrent || $isComplete ? 'background-color: var(--tenant-theme-accent);' : '' }}">
                                <span class="material-symbols-outlined text-[16px]">
                                    {{ $icons[$key] ?? 'circle' }}
                                </span>
                            </div>
                            @if (!$loop->last)
                                <div class="mt-1 h-8 w-0.5 {{ !$isComplete ? 'bg-gray-200 dark:bg-slate-700' : '' }}"
                                     style="{{ $isComplete ? 'background-color: var(--tenant-theme-accent);' : '' }}"></div>
                            @endif
                        </div>
                        <div class="pt-0.5">
                            <p class="text-sm {{ $isCurrent ? 'font-semibold text-gray-900 dark:text-slate-100' : ($isComplete ? 'text-gray-600 dark:text-slate-400' : 'text-gray-400 dark:text-slate-500') }}">
                                {{ $label }}
                            </p>
                            @if ($isCurrent)
                                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Current status</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Order Details --}}
            <div class="lg:col-span-2 bg-white shadow-sm rounded-2xl ring-1 ring-gray-100 dark:bg-slate-800 dark:ring-slate-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-slate-700">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-300">Order Details</h3>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5 text-sm">
                    @if ($order->service)
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-slate-500">Service</p>
                            <p class="mt-1 text-gray-800 dark:text-slate-200 font-medium">{{ $order->service->name }}</p>
                        </div>
                    @endif
                    @if ($order->weight)
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-slate-500">Weight</p>
                            <p class="mt-1 text-gray-800 dark:text-slate-200">{{ $order->weight }} kg</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-slate-500">Created</p>
                        <p class="mt-1 text-gray-800 dark:text-slate-200">{{ $order->created_at->format('M d, Y · h:i A') }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-slate-500">Due Date</p>
                        <p class="mt-1 text-gray-800 dark:text-slate-200">{{ $order->due_date?->format('M d, Y') ?? 'Not set' }}</p>
                    </div>
                    @if ($order->notes)
                        <div class="sm:col-span-2">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-slate-500">Notes</p>
                            <p class="mt-1 text-gray-700 dark:text-slate-300 whitespace-pre-line">{{ $order->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Payment summary --}}
            <div class="bg-white shadow-sm rounded-2xl ring-1 ring-gray-100 dark:bg-slate-800 dark:ring-slate-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-slate-700">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-300">Payment Summary</h3>
                </div>
                <div class="p-6 space-y-4 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-slate-400">Total amount</span>
                        <span class="font-semibold text-gray-900 dark:text-slate-100">₱{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                    @if (method_exists($order, 'outstandingBalance'))
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-slate-400">Outstanding</span>
                            <span class="font-semibold {{ $order->isPaid() ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500 dark:text-rose-400' }}">
                                ₱{{ number_format($order->isPaid() ? 0 : $order->outstandingBalance(), 2) }}
                            </span>
                        </div>
                    @endif
                    <div class="pt-4 border-t border-gray-100 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-slate-400">Status</span>
                            @if ($order->isPaid())
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-700">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                    Paid
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:ring-rose-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                    Unpaid
                                </span>
                            @endif
                        </div>
                    </div>

                    @if (!$order->isPaid() && tenant()->hasFeature('online_payments') && tenant()->paymongo_secret_key && $order->canBePaidOnline())
                        <form method="POST" action="{{ route('tenant.order-payments.checkout', $order) }}" class="pt-2">
                            @csrf
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-4 py-2.5 text-sm font-semibold text-white shadow-sm focus:outline-none focus:ring-2 {{ $theme['focus_ring'] }} transition">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                </svg>
                                Pay Online
                            </button>
                        </form>
                    @elseif ($order->isPaid() && $order->paid_at)
                        <p class="pt-2 text-xs text-gray-400 dark:text-slate-500">Paid on {{ $order->paid_at->format('M d, Y') }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Items --}}
        @if ($order->items && count($order->items))
            <div class="bg-white shadow-sm rounded-2xl ring-1 ring-gray-100 dark:bg-slate-800 dark:ring-slate-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-slate-700">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-300">Items</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-900/50">
                            <tr class="text-left text-[11px] text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                <th class="px-6 py-3 font-semibold">Item</th>
                                <th class="px-6 py-3 font-semibold text-center">Qty</th>
                                <th class="px-6 py-3 font-semibold text-right">Unit Price</th>
                                <th class="px-6 py-3 font-semibold text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            @foreach ($order->items as $item)
                                @php
                                    $qty = (int) ($item['qty'] ?? 1);
                                    $price = (float) ($item['price'] ?? 0);
                                @endphp
                                <tr>
                                    <td class="px-6 py-3 text-gray-900 dark:text-slate-100">{{ $item['name'] ?? 'Not set' }}</td>
                                    <td class="px-6 py-3 text-center text-gray-600 dark:text-slate-400">{{ $qty }}</td>
                                    <td class="px-6 py-3 text-right text-gray-600 dark:text-slate-400">₱{{ number_format($price, 2) }}</td>
                                    <td class="px-6 py-3 text-right font-medium text-gray-900 dark:text-slate-100">₱{{ number_format($qty * $price, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        @if ($itemsTotal > 0)
                            <tfoot class="bg-gray-50 dark:bg-slate-900/30">
                                <tr>
                                    <td colspan="3" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Items Total</td>
                                    <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-slate-100">₱{{ number_format($itemsTotal, 2) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-tenant-layout>
