<x-tenant-layout>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />

    @php 
        $theme = tenant()->getThemePreset(); 
        $isPaid = $order->isPaid();
    @endphp

    <div class="max-w-7xl mx-auto space-y-8 mt-2">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-3 mb-1">
                    <a href="{{ route('tenant.orders.index') }}" class="text-gray-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400 transition-colors flex items-center">
                        <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                    </a>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-slate-100">Order Details</h1>
                    <span class="px-3 py-1 bg-gray-100 dark:bg-slate-800 text-gray-900 dark:text-slate-100 text-sm font-semibold rounded-full {{ $order->status_color }}">
                        {{ $order->order_number }}
                    </span>
                </div>
                <p class="text-base text-gray-500 dark:text-slate-400 ml-9">Created on {{ $order->created_at->format('M d, Y') }} at {{ $order->created_at->format('h:i A') }}</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                @if (auth()->user()->isOwner() || auth()->user()->isStaff())
                    <a href="{{ route('tenant.orders.edit', $order) }}" class="px-4 py-2 border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-300 rounded-lg text-sm font-semibold hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors active:scale-[0.98] flex items-center space-x-2">
                        <span class="material-symbols-outlined text-[18px]">edit</span>
                        <span>Edit Order</span>
                    </a>
                    
                    @if (auth()->user()->isOwner())
                        <form method="POST" action="{{ route('tenant.orders.destroy', $order) }}" onsubmit="return confirm('Delete this order?')" class="m-0">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-4 py-2 border border-red-200 dark:border-red-900/50 text-red-600 dark:text-red-400 rounded-lg text-sm font-semibold hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors active:scale-[0.98] flex items-center space-x-2">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </form>
                    @endif
                @endif

                <a href="{{ route('tenant.orders.receipt', $order) }}" target="_blank" class="px-4 py-2 border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-300 rounded-lg text-sm font-semibold hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors active:scale-[0.98] flex items-center space-x-2">
                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                    <span>Print Receipt</span>
                </a>
                
                @if (auth()->user()->isOwner() || auth()->user()->isStaff())
                    @php
                        $nextStatuses = \App\Models\Order::nextStatusActionsForPlan($order->status);
                    @endphp
                    @foreach ($nextStatuses as $statusKey => $statusLabel)
                        <form method="POST" action="{{ route('tenant.orders.update-status', $order) }}" class="m-0">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $statusKey }}">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold shadow-sm hover:bg-blue-700 transition-colors active:scale-[0.98] flex items-center space-x-2">
                                <span class="material-symbols-outlined text-[18px]">play_arrow</span>
                                <span>{{ $statusLabel }}</span>
                            </button>
                        </form>
                    @endforeach
                @endif
            </div>
        </div>

        <!-- Bento Grid Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column (Status & Details) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Status Tracker Card -->
                <div class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 dark:border-slate-800 relative overflow-hidden">
                    <div class="absolute -top-12 -right-12 w-32 h-32 rounded-full blur-2xl pointer-events-none" style="background-color: var(--tenant-theme-accent-soft-strong);"></div>
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-slate-100 flex items-center space-x-2">
                            <span class="material-symbols-outlined" style="color: var(--tenant-theme-accent);">timeline</span>
                            <span>Order Lifecycle</span>
                        </h2>
                        <span class="px-3 py-1 text-sm font-semibold rounded-md" style="background-color: var(--tenant-theme-accent-soft); color: var(--tenant-theme-accent);">
                            {{ $order->status_label }}
                        </span>
                    </div>
                    
                    @php
                        $planStatuses = \App\Models\Order::statusLabelsForPlan();
                        $statusKeys = array_keys($planStatuses);
                        $statusIndex = array_search($order->status, $statusKeys);
                        if ($statusIndex === false) $statusIndex = 0;
                        
                        $totalSteps = count($statusKeys);
                        $progressWidth = $totalSteps > 1 ? ($statusIndex / ($totalSteps - 1)) * 100 : 0;
                        
                        $icons = [
                            'received' => 'inventory_2',
                            'in_progress' => 'local_laundry_service',
                            'washing' => 'water_drop',
                            'drying' => 'air',
                            'folding' => 'dry_cleaning',
                            'ready' => 'checkroom',
                            'claimed' => 'task_alt',
                        ];
                        
                        $steps = [];
                        foreach($statusKeys as $index => $key) {
                            $steps[] = [
                                'label' => $planStatuses[$key],
                                'icon' => $icons[$key] ?? 'circle',
                                'active' => $statusIndex >= $index,
                            ];
                        }
                    @endphp

                    <div class="relative flex justify-between mt-8 mb-4 px-4">
                        <!-- Progress Line (Single Continuous Line) -->
                        <div class="absolute -translate-y-1/2 h-1 bg-gray-200 dark:bg-slate-700" 
                             style="top: 20px; left: {{ 100 / (count($steps) * 2) }}%; right: {{ 100 / (count($steps) * 2) }}%;">
                            <div class="absolute left-0 top-0 bottom-0 transition-all duration-500" 
                                 style="width: {{ count($steps) > 1 ? ($statusIndex / (count($steps) - 1)) * 100 : 0 }}%; background-color: var(--tenant-theme-accent);"></div>
                        </div>

                        @foreach($steps as $index => $step)
                            <!-- Step {{ $index + 1 }} -->
                            <div class="relative flex flex-col items-center flex-1">
                                @if($step['active'])
                                    @if($statusIndex == $index)
                                        <!-- Current Step (Has Gap) -->
                                        <div class="relative z-10 w-10 h-10 rounded-full bg-white dark:bg-slate-900 border-2 flex items-center justify-center ring-4 ring-white dark:ring-slate-900 shadow-sm transition-transform hover:scale-110" style="border-color: var(--tenant-theme-accent); color: var(--tenant-theme-accent);">
                                            <span class="material-symbols-outlined text-[20px]">{{ $step['icon'] }}</span>
                                        </div>
                                    @else
                                        <!-- Completed Step (No Gap) -->
                                        <div class="relative z-10 w-10 h-10 rounded-full text-white flex items-center justify-center transition-transform hover:scale-110" style="background-color: var(--tenant-theme-accent);">
                                            <span class="material-symbols-outlined text-[20px]">{{ $step['icon'] }}</span>
                                        </div>
                                    @endif
                                    <span class="mt-3 text-[11px] font-bold uppercase tracking-wider text-gray-900 dark:text-slate-100 text-center" style="color: var(--tenant-theme-accent);">{{ $step['label'] }}</span>
                                @else
                                    <!-- Future Step (Has Gap) -->
                                    <div class="relative z-10 w-10 h-10 rounded-full bg-gray-100 dark:bg-slate-800 text-gray-400 dark:text-slate-500 flex items-center justify-center ring-4 ring-white dark:ring-slate-900 transition-transform hover:scale-110">
                                        <span class="material-symbols-outlined text-[20px]">{{ $step['icon'] }}</span>
                                    </div>
                                    <span class="mt-3 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500 text-center">{{ $step['label'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Laundry Items Table Card -->
                <div class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 dark:border-slate-800">
                    <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-slate-100 mb-6 flex items-center space-x-2">
                        <span class="material-symbols-outlined" style="color: var(--tenant-theme-accent);">category</span>
                        <span>Order Items</span>
                    </h2>
                    
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-slate-700">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-slate-800/80 text-gray-900 dark:text-slate-100 text-sm font-semibold border-b border-gray-200 dark:border-slate-700">
                                    <th class="py-3 px-4">Description</th>
                                    <th class="py-3 px-4 text-right">Qty</th>
                                    <th class="py-3 px-4 text-right">Unit Price</th>
                                    <th class="py-3 px-4 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="text-base text-gray-600 dark:text-slate-300">
                                @if ($order->items && count($order->items))
                                    @foreach ($order->items as $item)
                                        <tr class="border-b border-gray-100 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors h-[56px]">
                                            <td class="py-3 px-4 flex items-center space-x-3">
                                                <div class="w-8 h-8 rounded bg-gray-100 dark:bg-slate-800 flex items-center justify-center" style="color: var(--tenant-theme-accent);">
                                                    <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                                                </div>
                                                <span class="font-medium text-gray-900 dark:text-slate-100">{{ $item['name'] ?? 'Not set' }}</span>
                                            </td>
                                            <td class="py-3 px-4 text-right">{{ $item['qty'] ?? 1 }}</td>
                                            <td class="py-3 px-4 text-right">₱{{ number_format($item['price'] ?? 0, 2) }}</td>
                                            <td class="py-3 px-4 text-right font-medium text-gray-900 dark:text-slate-100">₱{{ number_format(($item['qty'] ?? 1) * ($item['price'] ?? 0), 2) }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="4" class="py-8 px-4 text-center text-gray-400 italic">No additional items recorded.</td>
                                    </tr>
                                @endif
                            </tbody>
                            @if ($order->items && count($order->items))
                            <tfoot class="bg-gray-50 dark:bg-slate-800/80 border-t border-gray-200 dark:border-slate-700 text-sm font-semibold text-gray-900 dark:text-slate-100">
                                <tr>
                                    <td class="py-3 px-4 text-right" colspan="2">Total Items:</td>
                                    <td class="py-3 px-4 text-right" colspan="2">{{ collect($order->items)->sum('qty') }}</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column (Customer & Payment) -->
            <div class="space-y-6">
                <!-- Customer Info Card -->
                <div class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 dark:border-slate-800 border-t-4 border-t-teal-500 relative overflow-hidden">
                    <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-slate-100 mb-6 flex items-center space-x-2">
                        <span class="material-symbols-outlined text-teal-600 dark:text-teal-400">person</span>
                        <span>Customer Info</span>
                    </h2>
                    
                    <div class="flex items-center space-x-4 mb-6">
                        <div class="w-14 h-14 rounded-full bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400 flex items-center justify-center text-xl font-bold">
                            {{ strtoupper(substr($order->customer->name, 0, 2)) }}
                        </div>
                        <div>
                            <a href="{{ route('tenant.customers.show', $order->customer) }}" class="text-[18px] font-bold tracking-tight text-gray-900 dark:text-slate-100 hover:text-teal-600 dark:hover:text-teal-400 transition-colors">
                                {{ $order->customer->name }}
                            </a>
                            <div class="flex items-center text-gray-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider mt-1">
                                <span class="material-symbols-outlined text-[14px] mr-1">star</span>
                                <span>Customer</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-4 text-base text-gray-600 dark:text-slate-300">
                        @if ($order->customer->phone)
                            <div class="flex items-start space-x-3">
                                <span class="material-symbols-outlined text-gray-400 dark:text-slate-500 mt-0.5 text-[20px]">phone</span>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-slate-100">{{ $order->customer->phone }}</div>
                                    <div class="text-sm">Mobile</div>
                                </div>
                            </div>
                        @endif
                        @if ($order->customer->email)
                            <div class="flex items-start space-x-3">
                                <span class="material-symbols-outlined text-gray-400 dark:text-slate-500 mt-0.5 text-[20px]">mail</span>
                                <div class="font-medium text-gray-900 dark:text-slate-100">{{ $order->customer->email }}</div>
                            </div>
                        @endif
                        @if ($order->customer->address)
                            <div class="flex items-start space-x-3">
                                <span class="material-symbols-outlined text-gray-400 dark:text-slate-500 mt-0.5 text-[20px]">location_on</span>
                                <div class="font-medium text-gray-900 dark:text-slate-100">{{ $order->customer->address }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Service Details Card -->
                <div class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 dark:border-slate-800 relative overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-24 h-24 rounded-full blur-xl pointer-events-none" style="background-color: var(--tenant-theme-accent-soft-strong);"></div>
                    <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-slate-100 mb-6 flex items-center space-x-2">
                        <span class="material-symbols-outlined" style="color: var(--tenant-theme-accent);">receipt</span>
                        <span>Service Details</span>
                    </h2>
                    
                    <div class="space-y-4 mb-6">
                        @if ($order->service)
                            <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-slate-800">
                                <span class="text-base text-gray-500 dark:text-slate-400">Service Type</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-slate-100 px-2 py-1 bg-gray-100 dark:bg-slate-800 rounded-md">{{ $order->service->name }}</span>
                            </div>
                        @endif
                        @if ($order->weight)
                            <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-slate-800">
                                <span class="text-base text-gray-500 dark:text-slate-400">Weight</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-slate-100 px-2 py-1 bg-gray-100 dark:bg-slate-800 rounded-md">{{ $order->weight }} kg</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-slate-800">
                            <span class="text-base text-gray-500 dark:text-slate-400">Expected Delivery</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-slate-100">{{ $order->due_date?->format('M d, Y') ?? 'Not set' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-slate-800">
                            <span class="text-base text-gray-500 dark:text-slate-400">Payment Status</span>
                            @if ($isPaid)
                                <span class="text-sm font-semibold text-green-700 dark:text-green-400 px-2 py-1 bg-green-100 dark:bg-green-900/30 rounded-md flex items-center space-x-1">
                                    <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                    <span>Paid</span>
                                </span>
                            @else
                                <span class="text-sm font-semibold text-red-600 dark:text-red-400 px-2 py-1 bg-red-100 dark:bg-red-900/30 rounded-md flex items-center space-x-1">
                                    <span class="material-symbols-outlined text-[14px]">error</span>
                                    <span>Unpaid</span>
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-slate-800 p-4 rounded-lg border border-gray-200 dark:border-slate-700">
                        @if($order->service)
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-base text-gray-500 dark:text-slate-400">Base Service</span>
                                <span class="text-base text-gray-900 dark:text-slate-100">₱{{ number_format(($order->service->price ?? 0) * ($order->weight ?? 1), 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center pt-3 border-t border-gray-200 dark:border-slate-700 mt-2">
                            <span class="text-[18px] font-bold tracking-tight text-gray-900 dark:text-slate-100">Total Amount</span>
                            <span class="text-[20px] font-bold tracking-tight text-blue-600 dark:text-blue-400">₱{{ number_format($order->total_amount, 2) }}</span>
                        </div>
                    </div>
                    
                    @if (! $isPaid && (auth()->user()->isOwner() || auth()->user()->isStaff()))
                        <form method="POST" action="{{ route('tenant.orders.mark-paid', $order) }}" class="mt-6">
                            @csrf @method('PATCH')
                            <button type="submit" class="w-full py-3 border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-300 rounded-lg text-sm font-semibold hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors active:scale-[0.98] flex items-center justify-center space-x-2">
                                <span class="material-symbols-outlined text-[18px]">payments</span>
                                <span>Mark as Paid</span>
                            </button>
                        </form>
                    @endif
                    
                    @if ($order->notes)
                        <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300 text-sm rounded-lg border border-yellow-200 dark:border-yellow-800">
                            <strong>Notes:</strong> {{ $order->notes }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-tenant-layout>
