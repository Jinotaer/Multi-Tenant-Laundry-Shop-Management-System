<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('App Releases') }}
            </h2>
            <form action="{{ route('admin.releases.sync') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25">
                    Sync from GitHub
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            
            @if (session('success'))
                <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-green-200 dark:text-green-800" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-200 dark:text-red-800" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Total Tenants: {{ $totalTenants }}</h3>

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Version</th>
                                    <th scope="col" class="px-6 py-3">Name</th>
                                    <th scope="col" class="px-6 py-3">Published</th>
                                    <th scope="col" class="px-6 py-3">Active Tenants</th>
                                    <th scope="col" class="px-6 py-3">Options</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($releases as $release)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                        {{ $release->version_tag }}
                                        @if($release->is_prerelease)
                                            <span class="bg-yellow-100 text-yellow-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded ml-2">Pre-release</span>
                                        @endif
                                        @if($release->is_required)
                                            <span class="bg-red-100 text-red-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded ml-2">Required</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $release->name }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $release->published_at ? $release->published_at->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="w-full bg-gray-200 rounded-full h-2.5 mb-1">
                                            @php $percentage = $totalTenants > 0 ? ($release->active_tenants_count / $totalTenants) * 100 : 0; @endphp
                                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ $release->active_tenants_count }} / {{ $totalTenants }} ({{ round($percentage, 1) }}%)</span>
                                    </td>
                                    <td class="px-6 py-4 flex space-x-3">
                                        <a href="{{ route('admin.releases.show', $release->id) }}" class="font-medium text-blue-600 hover:underline">View</a>
                                        
                                        <form action="{{ route('admin.releases.force-all', $release->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to FORCE all tenants to update to {{ $release->version_tag }}?');">
                                            @csrf
                                            <button type="submit" class="font-medium text-red-600 hover:underline">Force All</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        No releases found. Click 'Sync from GitHub' above to fetch releases.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $releases->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>