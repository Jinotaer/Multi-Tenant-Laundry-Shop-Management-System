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

            <div class="p-6 bg-white shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Force-All Runs</h3>

                @if($forceRuns->isEmpty())
                    <p class="text-sm text-gray-500">No force-all history for this release yet.</p>
                @else
                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Run Time</th>
                                    <th scope="col" class="px-6 py-3">Triggered By</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3">Deployment</th>
                                    <th scope="col" class="px-6 py-3">Tenant Results</th>
                                    <th scope="col" class="px-6 py-3">Failed Tenant IDs</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($forceRuns as $run)
                                    <tr class="bg-white border-b">
                                        <td class="px-6 py-4">
                                            {{ $run->started_at?->format('M d, Y H:i') ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ $run->admin?->name ?? 'System/Unknown' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($run->status === 'completed')
                                                <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Completed</span>
                                            @elseif($run->status === 'partial')
                                                <span class="inline-flex rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">Partial</span>
                                            @elseif($run->status === 'failed')
                                                <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Failed</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">{{ ucfirst($run->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($run->deployment_success)
                                                <span class="text-green-700">Success</span>
                                            @else
                                                <span class="text-red-700">Failed</span>
                                                @if($run->deployment_error)
                                                    <p class="mt-1 text-xs text-red-600">{{ $run->deployment_error }}</p>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ $run->successful_tenants }} / {{ $run->total_tenants }} updated
                                        </td>
                                        <td class="px-6 py-4">
                                            @if(!empty($run->failed_tenant_ids))
                                                <span class="text-xs">{{ implode(', ', $run->failed_tenant_ids) }}</span>
                                            @else
                                                <span class="text-xs text-gray-400">None</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-admin-layout>