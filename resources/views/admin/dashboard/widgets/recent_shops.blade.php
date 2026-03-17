<section class="tenant-panel overflow-hidden">
    <div class="p-6 text-gray-900 dark:text-slate-100">
        <h3 class="text-lg font-semibold">Recent Shops</h3>

        @if ($recentTenants->isEmpty())
            <p class="mt-4 text-sm text-gray-500 dark:text-slate-400">No shops registered yet.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                    <thead class="bg-gray-50 dark:bg-slate-950/60">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Shop Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                        @foreach ($recentTenants as $tenant)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-slate-100">{{ $tenant->id }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-slate-100">{{ $tenant->data['shop_name'] ?? $tenant->id }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-slate-400">{{ $tenant->created_at->diffForHumans() }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <a href="{{ route('admin.tenants.show', $tenant) }}" class="tenant-auth-link font-medium">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
