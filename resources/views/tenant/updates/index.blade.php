<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-slate-100 leading-tight">
            {{ __('Update Center') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if (session('success'))
                <div class="rounded-lg bg-green-50 p-4 text-sm text-green-700 border border-green-200 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700 border border-red-200 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400">
                    {{ session('error') }}
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white dark:bg-slate-900 shadow sm:rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-slate-100">Current Version: {{ $currentVersion }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-slate-400">Your application is currently running this version.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($availableUpdates->isEmpty())
                            <span class="inline-flex items-center gap-2 rounded-full bg-green-100 dark:bg-green-900/30 px-3 py-1 text-sm font-medium text-green-700 dark:text-green-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Up to date
                            </span>
                        @else
                            <span class="inline-flex items-center gap-2 rounded-full bg-blue-100 dark:bg-blue-900/30 px-3 py-1 text-sm font-medium text-blue-700 dark:text-blue-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                {{ $availableUpdates->count() }} {{ Str::plural('update', $availableUpdates->count()) }} available
                            </span>
                        @endif
                        <button onclick="window.location.reload()" class="inline-flex items-center gap-2 px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-md font-medium text-xs text-gray-700 dark:text-slate-300 uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            Refresh
                        </button>
                    </div>
                </div>
            </div>

            @if($availableUpdates->isNotEmpty())
            <div class="p-4 sm:p-8 bg-blue-50 dark:bg-blue-900/20 shadow sm:rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <h3 class="text-lg font-medium text-blue-900 dark:text-blue-100">Available Updates</h3>
                </div>
                
                <div class="mt-4 space-y-4">
                    @foreach($availableUpdates as $release)
                    <div class="bg-white dark:bg-slate-900 p-4 rounded-lg border border-blue-100 dark:border-blue-800 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h4 class="font-bold text-gray-900 dark:text-slate-100">{{ $release->version_tag }}</h4>
                                    @if($release->is_required)
                                        <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-900/30 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-400">
                                            Required
                                        </span>
                                    @endif
                                    @if($release->is_prerelease)
                                        <span class="inline-flex items-center rounded-full bg-yellow-100 dark:bg-yellow-900/30 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:text-yellow-400">
                                            Pre-release
                                        </span>
                                    @endif
                                </div>
                                <p class="text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">{{ $release->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-slate-400 mb-3">Published {{ $release->published_at->diffForHumans() }}</p>
                                <div class="text-sm text-gray-700 dark:text-slate-300 prose prose-sm dark:prose-invert max-w-none">
                                    {!! Str::markdown($release->body ?? 'No release notes available.') !!}
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <form action="{{ route('tenant.updates.apply', $release->id) }}" method="POST" onsubmit="return confirm('A backup will be created before updating. Continue?');">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                        Update Now
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="p-4 sm:p-8 bg-white dark:bg-slate-900 shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-slate-100 mb-4">Version History</h3>
                <p class="text-sm text-gray-600 dark:text-slate-400 mb-4">View your update history and current version status.</p>
                
                @if($updateHistory->isEmpty())
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">No version history available yet.</p>
                    </div>
                @else
                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-slate-400">
                            <thead class="text-xs text-gray-700 dark:text-slate-300 uppercase bg-gray-50 dark:bg-slate-800">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Version</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3">Action Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($updateHistory as $history)
                                <tr class="bg-white dark:bg-slate-900 border-b dark:border-slate-800">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-slate-100 whitespace-nowrap">
                                        {{ $history->release->version_tag }}
                                        @if($history->is_current)
                                        <span class="bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 text-xs font-medium mr-2 px-2.5 py-0.5 rounded ml-2">Active</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ ucfirst(str_replace('_', ' ', $history->status)) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $history->action_taken_at ? $history->action_taken_at->format('M d, Y H:i') : 'N/A' }}
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
</x-tenant-layout>