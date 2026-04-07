<x-tenant-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Billing & Invoices') }}
            </h2>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="tenant-panel overflow-hidden p-6">
                <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Total Invoices</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($invoices->total()) }}</p>
            </div>

            <div class="tenant-panel overflow-hidden p-6">
                <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Total Paid</p>
                <p class="mt-2 text-2xl font-bold text-green-600 dark:text-green-400">₱{{ number_format((float) $totalPaid, 2) }}</p>
            </div>

            <div class="tenant-panel overflow-hidden p-6">
                <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Pending Amount</p>
                <p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">₱{{ number_format((float) $pendingAmount, 2) }}</p>
            </div>
        </div>

        <div class="tenant-panel overflow-hidden">
            <div class="p-6 text-gray-900 dark:text-slate-100">
                @if ($invoices->isEmpty())
                    <div class="py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-slate-100">No invoices yet</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Your subscription invoices will appear here once payments are recorded.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                            <thead class="bg-gray-50 dark:bg-slate-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Invoice #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Plan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Issued</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-slate-700 dark:bg-slate-900">
                                @foreach ($invoices as $invoice)
                                    <tr>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <a href="{{ route('tenant.billing.show', $invoice) }}" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                                                {{ $invoice->invoice_number }}
                                            </a>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-slate-300">
                                            {{ $invoice->subscriptionPlan?->name ?? 'Subscription' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-slate-100">
                                            {{ $invoice->formatted_total }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                                {{ $invoice->status_color === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                                {{ $invoice->status_color === 'blue' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                                {{ $invoice->status_color === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                                {{ $invoice->status_color === 'gray' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400' : '' }}">
                                                {{ $invoice->status_label }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-slate-400">
                                            {{ $invoice->issue_date?->format('M d, Y') }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                                            <a href="{{ route('tenant.billing.show', $invoice) }}" class="mr-4 text-indigo-600 hover:underline dark:text-indigo-400">View</a>
                                            <a href="{{ route('tenant.billing.download', $invoice) }}" class="text-gray-600 hover:underline dark:text-slate-400">Download</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $invoices->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-tenant-layout>