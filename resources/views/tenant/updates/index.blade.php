<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Update Center') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900">Current Version: {{ $currentVersion }}</h3>
                <p class="mt-1 text-sm text-gray-600">Your application is currently running this version.</p>
            </div>

            @if($availableUpdates->isNotEmpty())
            <div class="p-4 sm:p-8 bg-blue-50 shadow sm:rounded-lg border border-blue-200">
                <h3 class="text-lg font-medium text-blue-900">Available Updates</h3>
                
                <div class="mt-4 space-y-4">
                    @foreach($availableUpdates as $update)
                    <div class="bg-white p-4 rounded border border-blue-100 shadow-sm flex items-start justify-between">
                        <div>
                            <h4 class="font-bold text-gray-900">{{ $update->release->version_tag }} - {{ $update->release->name }}</h4>
                            <p class="text-xs text-gray-500 mt-1">Published: {{ $update->release->published_at->diffForHumans() }}</p>
                            <div class="mt-2 text-sm text-gray-700 prose prose-sm">
                                {!! Str::markdown($update->release->body) !!}
                            </div>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <form action="{{ route('tenant.updates.apply', $update->release->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Update Now
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Version History & Rollbacks</h3>
                
                <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3">Version</th>
                                <th scope="col" class="px-6 py-3">Status</th>
                                <th scope="col" class="px-6 py-3">Action Date</th>
                                <th scope="col" class="px-6 py-3">Options</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($updateHistory as $history)
                            <tr class="bg-white border-b">
                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                    {{ $history->release->version_tag }}
                                    @if($history->is_current)
                                    <span class="bg-green-100 text-green-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded ml-2">Active</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    {{ ucfirst(str_replace('_', ' ', $history->status)) }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $history->action_taken_at ? $history->action_taken_at->format('M d, Y H:i') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    @if(!$history->is_current)
                                    <form action="{{ route('tenant.updates.rollback', $history->release->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to rollback to this version?');">
                                        @csrf
                                        <button type="submit" class="font-medium text-red-600 hover:underline">Rollback</button>
                                    </form>
                                    @else
                                    <span class="text-gray-400">Current</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-tenant-layout>