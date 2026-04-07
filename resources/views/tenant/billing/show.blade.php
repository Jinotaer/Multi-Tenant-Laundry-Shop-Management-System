<x-tenant-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <a href="{{ route('tenant.billing.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200">
                    {{ __('← Back to Billing') }}
                </a>
                <h2 class="mt-1 font-semibold text-xl leading-tight text-gray-800">
                    {{ __('Invoice') }} {{ $invoice->invoice_number }}
                </h2>
            </div>
            <a href="{{ route('tenant.billing.download', $invoice) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800">
                Download
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="tenant-panel overflow-hidden">
            <div class="p-8">
                <div class="mb-8 flex items-start justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-slate-100">INVOICE</h3>
                        <p class="mt-1 text-gray-500 dark:text-slate-400">{{ $invoice->invoice_number }}</p>
                    </div>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium
                        {{ $invoice->status_color === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                        {{ $invoice->status_color === 'blue' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                        {{ $invoice->status_color === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                        {{ $invoice->status_color === 'gray' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400' : '' }}">
                        {{ $invoice->status_label }}
                    </span>
                </div>

                <div class="mb-8 grid grid-cols-1 gap-8 md:grid-cols-2">
                    <div>
                        <h4 class="mb-2 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">From</h4>
                        <p class="font-medium text-gray-900 dark:text-slate-100">{{ config('app.name') }}</p>
                        <p class="text-sm text-gray-500 dark:text-slate-400">support@laundrytrack.com</p>
                    </div>
                    <div>
                        <h4 class="mb-2 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Bill To</h4>
                        <p class="font-medium text-gray-900 dark:text-slate-100">{{ $invoice->billing_name }}</p>
                        <p class="text-sm text-gray-500 dark:text-slate-400">{{ $invoice->billing_email }}</p>
                        @if ($invoice->billing_address)
                            <p class="text-sm text-gray-500 dark:text-slate-400">{{ $invoice->billing_address }}</p>
                        @endif
                    </div>
                </div>

                <div class="mb-8 grid grid-cols-1 gap-4 rounded-lg bg-gray-50 p-4 md:grid-cols-3 dark:bg-slate-800">
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

                <div class="mb-8 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead>
                            <tr>
                                <th class="pb-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Description</th>
                                <th class="pb-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            <tr>
                                <td class="py-4">
                                    <p class="font-medium text-gray-900 dark:text-slate-100">{{ $invoice->subscriptionPlan?->name ?? 'Subscription' }} Plan</p>
                                    @if ($invoice->period_start && $invoice->period_end)
                                        <p class="text-sm text-gray-500 dark:text-slate-400">Period: {{ $invoice->period_start->format('M d, Y') }} - {{ $invoice->period_end->format('M d, Y') }}</p>
                                    @endif
                                    @if ($invoice->subscriptionPlan)
                                        <p class="text-sm text-gray-500 dark:text-slate-400">Billing: {{ ucfirst($invoice->subscriptionPlan->billing_cycle) }}</p>
                                    @endif
                                </td>
                                <td class="py-4 text-right font-medium text-gray-900 dark:text-slate-100">{{ $invoice->formatted_subtotal }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-200 pt-4 dark:border-slate-700">
                    <div class="ml-auto w-full max-w-xs space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-slate-400">Subtotal</span>
                            <span class="font-medium text-gray-900 dark:text-slate-100">{{ $invoice->formatted_subtotal }}</span>
                        </div>
                        @if ((float) $invoice->tax_amount > 0)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-slate-400">Tax ({{ $invoice->tax_rate }}%)</span>
                                <span class="font-medium text-gray-900 dark:text-slate-100">₱{{ number_format((float) $invoice->tax_amount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between border-t border-gray-200 pt-2 text-base font-bold dark:border-slate-700">
                            <span class="text-gray-900 dark:text-slate-100">Total</span>
                            <span class="text-gray-900 dark:text-slate-100">{{ $invoice->formatted_total }}</span>
                        </div>
                    </div>
                </div>

                @if ($invoice->notes)
                    <div class="mt-8 rounded-lg bg-gray-50 p-4 dark:bg-slate-800">
                        <h4 class="mb-2 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Notes</h4>
                        <p class="text-sm text-gray-700 dark:text-slate-200">{{ $invoice->notes }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-tenant-layout>