<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('All Shops') }}
        </h2>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($tenants->isEmpty())
                        <p class="text-gray-500">No shops registered yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shop Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($tenants as $tenant)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $tenant->data['shop_name'] ?? 'N/A' }}</div>
                                                <div class="text-xs text-gray-400">ID: {{ $tenant->id }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if ($tenant->registration)
                                                    <div class="text-sm text-gray-900">{{ $tenant->registration->owner_name }}</div>
                                                    <div class="text-xs text-gray-400">{{ $tenant->registration->owner_email }}</div>
                                                @else
                                                    <span class="text-sm text-gray-400">N/A</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @foreach ($tenant->domains as $domain)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        {{ $domain->domain }}
                                                    </span>
                                                @endforeach
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if ($tenant->subscriptionPlan)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">{{ $tenant->subscriptionPlan->name }}</span>
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if ($tenant->isEnabled())
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Disabled</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $tenant->created_at->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="flex items-center space-x-3">
                                                    <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-indigo-600 hover:text-indigo-900">View</a>

                                                    <form method="POST" action="{{ route('admin.tenants.toggle-status', $tenant) }}" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        @if ($tenant->isEnabled())
                                                            <button type="submit" class="text-yellow-600 hover:text-yellow-900" onclick="return confirm('Disable this shop? Users will not be able to access it.')">Disable</button>
                                                        @else
                                                            <button type="submit" class="text-green-600 hover:text-green-900" onclick="return confirm('Enable this shop?')">Enable</button>
                                                        @endif
                                                    </form>

                                                    <button type="button" class="text-red-600 hover:text-red-900" x-data x-on:click="$dispatch('open-modal', 'delete-tenant-{{ $tenant->id }}')">Delete</button>

                                                    <x-modal name="delete-tenant-{{ $tenant->id }}" focusable>
                                                        <form method="POST" action="{{ route('admin.tenants.destroy', $tenant) }}" class="p-6">
                                                            @csrf
                                                            @method('DELETE')

                                                            <h2 class="text-lg font-medium text-gray-900">
                                                                {{ __('Are you sure you want to delete this shop?') }}
                                                            </h2>

                                                            <p class="mt-1 text-sm text-gray-600">
                                                                {{ __('This will permanently delete all data for') }} <strong>{{ $tenant->data['shop_name'] ?? $tenant->id }}</strong>.
                                                            </p>

                                                            <div class="mt-6 flex justify-end">
                                                                <x-secondary-button x-on:click="$dispatch('close')">
                                                                    {{ __('Cancel') }}
                                                                </x-secondary-button>

                                                                <x-danger-button class="ms-3">
                                                                    {{ __('Delete Shop') }}
                                                                </x-danger-button>
                                                            </div>
                                                        </form>
                                                    </x-modal>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $tenants->links() }}
                        </div>
                    @endif
                </div>
    </div>
</x-admin-layout>
