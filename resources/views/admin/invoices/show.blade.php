<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <a href="{{ route('admin.invoices.index') }}" class="text-sm text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300">
                    ← Back to Invoices
                </a>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight mt-1">
                    Invoice {{ $invoice->invoice_number }}
                </h2>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.invoices.download', $invoice) }}">
                    <x-secondary-button type="button">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Download
                    </x-secondary-button>
                </a>
                @if (!$invoice->isPaid())
                    <form method="POST" action="{{ route('admin.invoices.mark-paid', $invoice) }}" class="inline">
                        @csrf
                        <x-primary-button type="submit">
                            Mark as Paid
                        </x-primary-button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Invoice Card -->
        <div class="tenant-panel overflow-hidden">
            <div class="p-8">
                <!-- Header -->
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">INVOICE</h1>
                        <p class="text-gray-500 dark:text-slate-400 mt-1">{{ $invoice->invoice_number }}</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            {{ $invoice->status_color === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                            {{ $invoice->status_color === 'blue' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                            {{ $invoice->status_color === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                            {{ $invoice->status_color === 'gray' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400' : '' }}">
                            {{ $invoice->status_label }}
                        </span>
                    </div>
                </div>

                <!-- Addresses -->
                <div class="grid grid-cols-2 gap-8 mb-8">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-2">From</h3>
                        <p class="text-gray-900 dark:text-slate-100 font-medium">LaundryPro SaaS</p>
                        <p class="text-gray-500 dark:text-slate-400 text-sm">admin@laundrypro.com</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-2">Bill To</h3>
                        <p class="text-gray-900 dark:text-slate-100 font-medium">{{ $invoice->billing_name }}</p>
                        <p class="text-gray-500 dark:text-slate-400 text-sm">{{ $invoice->billing_email }}</p>
                        @if ($invoice->billing_address)
                            <p class="text-gray-500 dark:text-slate-400 text-sm">{{ $invoice->billing_address }}</p>
                        @endif
                        <p class="text-gray-500 dark:text-slate-400 text-sm mt-1">
                            Shop: {{ $invoice->tenant->registration->shop_name ?? $invoice->tenant_id }}
                        </p>
                    </div>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-3 gap-4 mb-8 p-4 bg-gray-50 dark:bg-slate-800 rounded-lg">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Issue Date</p>
                        <p class="font-medium text-gray-900 dark:text-slate-100">{{ $invoice->issue_date->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Due Date</p>
                        <p class="font-medium text-gray-900 dark:text-slate-100">{{ $invoice->due_date->format('M d, Y') }}</p>
                    </div>
                    @if ($invoice->paid_at)
                        <div>
                            <p class="text-sm text-gray-500 dark:text-slate-400">Paid Date</p>
                            <p class="font-medium text-green-600 dark:text-green-400">{{ $invoice->paid_at->format('M d, Y') }}</p>
                        </div>
                    @endif
                </div>

                <!-- Line Items -->
                <div class="mb-8">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead>
                            <tr>
                                <th class="text-left text-sm font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider pb-3">Description</th>
                                <th class="text-right text-sm font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider pb-3">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            <tr>
                                <td class="py-4">
                                    <p class="text-gray-900 dark:text-slate-100 font-medium">
                                        {{ $invoice->subscriptionPlan?->name ?? 'Subscription' }} Plan
                                    </p>
                                    @if ($invoice->period_start && $invoice->period_end)
                                        <p class="text-gray-500 dark:text-slate-400 text-sm">
                                            Period: {{ $invoice->period_start->format('M d, Y') }} - {{ $invoice->period_end->format('M d, Y') }}
                                        </p>
                                    @endif
                                    @if ($invoice->subscriptionPlan)
                                        <p class="text-gray-500 dark:text-slate-400 text-sm">
                                            Billing: {{ ucfirst($invoice->subscriptionPlan->billing_cycle) }}
                                        </p>
                                    @endif
                                </td>
                                <td class="py-4 text-right text-gray-900 dark:text-slate-100">
                                    {{ $invoice->formatted_subtotal }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                    <div class="flex justify-end">
                        <div class="w-64">
                            <div class="flex justify-between py-2">
                                <span class="text-gray-500 dark:text-slate-400">Subtotal</span>
                                <span class="text-gray-900 dark:text-slate-100">{{ $invoice->formatted_subtotal }}</span>
                            </div>
                            @if ($invoice->tax_amount > 0)
                                <div class="flex justify-between py-2">
                                    <span class="text-gray-500 dark:text-slate-400">Tax ({{ $invoice->tax_rate }}%)</span>
                                    <span class="text-gray-900 dark:text-slate-100">₱{{ number_format($invoice->tax_amount, 2) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between py-2 border-t border-gray-200 dark:border-slate-700 font-bold">
                                <span class="text-gray-900 dark:text-slate-100">Total</span>
                                <span class="text-gray-900 dark:text-slate-100">{{ $invoice->formatted_total }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                @if ($invoice->notes)
                    <div class="mt-8 p-4 bg-gray-50 dark:bg-slate-800 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-2">Notes</h3>
                        <p class="text-gray-700 dark:text-slate-300">{{ $invoice->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Payment Info -->
        @if ($invoice->payment)
            <div class="tenant-panel overflow-hidden p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">Payment Information</h3>
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-slate-400">Payment Method</dt>
                        <dd class="text-gray-900 dark:text-slate-100 font-medium">{{ ucfirst($invoice->payment->payment_method ?? 'N/A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-slate-400">Payment ID</dt>
                        <dd class="text-gray-900 dark:text-slate-100 font-mono text-sm">{{ $invoice->payment->paymongo_payment_id ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-slate-400">Status</dt>
                        <dd class="text-gray-900 dark:text-slate-100 font-medium">{{ ucfirst($invoice->payment->status) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-slate-400">Paid At</dt>
                        <dd class="text-gray-900 dark:text-slate-100">{{ $invoice->payment->paid_at?->format('M d, Y H:i') ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>
        @endif

        <!-- Tenant Info -->
        <div class="tenant-panel overflow-hidden p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">Tenant Information</h3>
            <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <dt class="text-sm text-gray-500 dark:text-slate-400">Shop Name</dt>
                    <dd class="text-gray-900 dark:text-slate-100 font-medium">{{ $invoice->tenant->registration->shop_name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-slate-400">Tenant ID</dt>
                    <dd class="text-gray-900 dark:text-slate-100 font-mono text-sm">{{ $invoice->tenant_id }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-slate-400">Current Plan</dt>
                    <dd class="text-gray-900 dark:text-slate-100">{{ $invoice->tenant->subscriptionPlan?->name ?? 'None' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-slate-400">Status</dt>
                    <dd>
                        @if ($invoice->tenant->is_enabled)
                            <span class="text-green-600 dark:text-green-400 font-medium">Active</span>
                        @else
                            <span class="text-red-600 dark:text-red-400 font-medium">Disabled</span>
                        @endif
                    </dd>
                </div>
            </dl>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-slate-700">
                <a href="{{ route('admin.tenants.show', $invoice->tenant) }}" class="tenant-link text-sm">
                    View Tenant Details →
                </a>
            </div>
        </div>
    </div>
</x-admin-layout>
