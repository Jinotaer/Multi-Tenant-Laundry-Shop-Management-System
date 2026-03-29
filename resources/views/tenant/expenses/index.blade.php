<x-tenant-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Expense Tracking
            </h2>
        </div>
    </x-slot>

    @php $theme = app(\App\Services\ThemeService::class)->getTenantTheme(); @endphp

    <div class="flex justify-end p-4">
        <a href="{{ route('tenant.expenses.create') }}"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition ease-in-out duration-150">
            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Record Expense
        </a>
    </div>
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="text-sm font-medium text-gray-500">Total Expenses (All Time)</div>
                <div class="mt-2 text-3xl font-bold text-gray-900">₱{{ number_format($totalAmount, 2) }}</div>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="text-sm font-medium text-gray-500">This Month's Expenses</div>
                <div class="mt-2 text-3xl font-bold text-gray-900">₱{{ number_format($monthlyTotal, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category"
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Categories</option>
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                    <input type="month" name="month" value="{{ request('month') }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="flex items-end">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition ease-in-out duration-150">
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Expenses Table -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        @if ($expenses->isEmpty())
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No expenses recorded</h3>
                <p class="mt-1 text-sm text-gray-500">Start tracking your operational expenses.</p>
                <div class="mt-6">
                    <a href="{{ route('tenant.expenses.create') }}"
                        class="inline-flex items-center px-4 py-2 {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition ease-in-out duration-150">
                        Record First Expense
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Description</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-right">
                                Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($expenses as $expense)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $expense->expense_date->format('M d, Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $theme['badge_bg'] }} {{ $theme['badge_text'] }}">
                                        {{ $expense->category_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $expense->description }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                    ₱{{ number_format($expense->amount, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm space-x-3">
                                    <a href="{{ route('tenant.expenses.edit', $expense) }}"
                                        class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                    <form method="POST" action="{{ route('tenant.expenses.destroy', $expense) }}" class="inline"
                                        onsubmit="return confirm('Delete this expense?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-gray-50 border-t border-gray-200 px-6 py-4">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>
</x-tenant-layout>