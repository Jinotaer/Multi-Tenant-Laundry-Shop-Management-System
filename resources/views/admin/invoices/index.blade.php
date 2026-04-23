<x-admin-layout>
    <x-slot name="header">
        <x-admin-header title="Billing & Invoices" description="Manage tenant subscriptions and payments." :actions=" [
            ['type' => 'form', 'method' => 'POST', 'action' => route('admin.invoices.generate-all'), 'confirm' => 'Generate invoices for all paid subscriptions without invoices?', 'label' => 'Generate Missing Invoices', 'icon' => 'heroicon-o-plus'],
            ['type' => 'button', 'href' => route('admin.invoices.export', request()->query()), 'label' => 'Export CSV', 'icon' => 'heroicon-o-download', 'color' => 'secondary']
        ]" />
    </x-slot>

    <div class="space-y-6">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="tenant-panel overflow-hidden p-6">
                <div class="text-sm font-medium text-gray-500 dark:text-slate-400">Total Invoices</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-slate-100 mt-2">{{ number_format($stats['total']) }}</div>
            </div>
            <div class="tenant-panel overflow-hidden p-6">
                <div class="text-sm font-medium text-gray-500 dark:text-slate-400">Paid</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400 mt-2">{{ number_format($stats['paid']) }}</div>
            </div>
            <div class="tenant-panel overflow-hidden p-6">
                <div class="text-sm font-medium text-gray-500 dark:text-slate-400">Unpaid</div>
                <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-2">{{ number_format($stats['unpaid']) }}</div>
            </div>
            <div class="tenant-panel overflow-hidden p-6">
                <div class="text-sm font-medium text-gray-500 dark:text-slate-400">Total Revenue</div>
                <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-2">₱{{ number_format($stats['total_revenue'], 2) }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="tenant-panel overflow-hidden p-6">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Invoice # or name..."
                           class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Status</label>
                    <select name="status" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="all">All Statuses</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="issued" {{ request('status') === 'issued' ? 'selected' : '' }}>Issued</option>
                        <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Tenant</label>
                    <select name="tenant" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Tenants</option>
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}" {{ request('tenant') === $tenant->id ? 'selected' : '' }}>
                                {{ $tenant->registration->shop_name ?? $tenant->id }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button type="submit">Filter</x-primary-button>
                @if (request()->hasAny(['search', 'status', 'tenant']))
                    <a href="{{ route('admin.invoices.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-300">
                        Clear filters
                    </a>
                @endif
            </form>
        </div>

        <!-- Invoices Table -->
        <div class="tenant-panel overflow-hidden">
            <div class="p-6 text-gray-900 dark:text-slate-100">
                @if ($invoices->isEmpty())
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-slate-100">No invoices</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                            Invoices will appear here when tenants make subscription payments.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                            <thead class="bg-gray-50 dark:bg-slate-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Invoice #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Shop</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Plan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Issue Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
                                @foreach ($invoices as $invoice)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                                {{ $invoice->invoice_number }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100">
                                                {{ $invoice->tenant->registration->shop_name ?? $invoice->tenant_id }}
                                            </div>
                                            <div class="text-xs text-gray-400 dark:text-slate-500">{{ $invoice->billing_email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                            {{ $invoice->subscriptionPlan?->name ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-slate-100">
                                            {{ $invoice->formatted_total }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $invoice->status_color === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                                {{ $invoice->status_color === 'blue' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                                {{ $invoice->status_color === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                                {{ $invoice->status_color === 'gray' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400' : '' }}">
                                                {{ $invoice->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                            {{ $invoice->issue_date->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline mr-3">
                                                View
                                            </a>
                                            <a href="{{ route('admin.invoices.download', $invoice) }}" class="text-gray-600 dark:text-gray-400 hover:underline">
                                                Download
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $invoices->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
