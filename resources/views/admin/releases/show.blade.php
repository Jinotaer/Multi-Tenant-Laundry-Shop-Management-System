<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.releases.index') }}" class="text-gray-500 hover:text-gray-700">
                &larr; Back
            </a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Release: {{ $release->version_tag }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
            
            <div class="p-6 bg-white shadow sm:rounded-lg">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ $release->name }}</h3>
                    <div class="text-sm text-gray-500">
                        Published: {{ $release->published_at ? $release->published_at->format('M d, Y H:i') : 'N/A' }}
                    </div>
                </div>
                
                <div class="prose max-w-none text-gray-700 bg-gray-50 p-4 rounded border">
                    {!! Str::markdown($release->body ?? 'No release notes provided.') !!}
                </div>
            </div>

            <div class="p-6 bg-white shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Tenants Currently on this Version</h3>
                
                <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3">Tenant ID</th>
                                <th scope="col" class="px-6 py-3">Subdomain</th>
                                <th scope="col" class="px-6 py-3">Updated At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tenantUpdates as $update)
                            <tr class="bg-white border-b">
                                <td class="px-6 py-4">{{ $update->tenant->id }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $update->tenant->domains->first()?->domain ?? $update->tenant->id }}</td>
                                <td class="px-6 py-4">{{ $update->action_taken_at ? $update->action_taken_at->format('M d, Y H:i') : 'N/A' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                                    No tenants are currently using this version.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $tenantUpdates->links() }}
                </div>
            </div>

        </div>
    </div>
</x-admin-layout>