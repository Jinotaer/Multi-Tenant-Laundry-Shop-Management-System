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
                                <form action="{{ route('tenant.updates.apply', $release->id) }}" method="POST" class="js-update-action" data-action-label="Updating to {{ $release->version_tag }}" onsubmit="return confirm('A backup will be created before updating. Continue?');">
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

            @if($rollbackCandidates->isNotEmpty())
            <div class="p-4 sm:p-8 bg-amber-50 dark:bg-amber-900/20 shadow sm:rounded-lg border border-amber-200 dark:border-amber-800">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25 4.5 9.75m0 0L9 5.25m-4.5 4.5H16.5A2.25 2.25 0 0 1 18.75 12v6.75" />
                    </svg>
                    <h3 class="text-lg font-medium text-amber-900 dark:text-amber-100">Available Rollbacks</h3>
                </div>

                <p class="text-sm text-amber-800 dark:text-amber-200 mb-4">
                    Roll back to an older release if you need to undo a recent change. A fresh backup will be created first.
                </p>

                <div class="mt-4 space-y-4">
                    @foreach($rollbackCandidates as $release)
                    <div class="bg-white dark:bg-slate-900 p-4 rounded-lg border border-amber-100 dark:border-amber-800 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h4 class="font-bold text-gray-900 dark:text-slate-100">{{ $release->version_tag }}</h4>
                                    <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300">
                                        Older release
                                    </span>
                                </div>
                                <p class="text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">{{ $release->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-slate-400 mb-3">Published {{ $release->published_at->diffForHumans() }}</p>

                                @if(!empty($release->rollback_warnings ?? []))
                                    <div class="mb-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                                        {{ implode(' ', $release->rollback_warnings) }}
                                    </div>
                                @endif

                                @if(!empty($release->rollback_errors ?? []))
                                    <div class="mb-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                                        {{ implode(' ', $release->rollback_errors) }}
                                    </div>
                                @endif

                                <div class="text-sm text-gray-700 dark:text-slate-300 prose prose-sm dark:prose-invert max-w-none">
                                    {!! Str::markdown($release->body ?? 'No release notes available.') !!}
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                @if($release->can_rollback && $canApplyUpdates)
                                    <form action="{{ route('tenant.updates.rollback', $release->id) }}" method="POST" class="js-update-action" data-action-label="Rolling back to {{ $release->version_tag }}" onsubmit="return confirm('A backup will be created before rollback. Continue?');">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25 4.5 9.75m0 0L9 5.25m-4.5 4.5H16.5A2.25 2.25 0 0 1 18.75 12v6.75" />
                                            </svg>
                                            Rollback
                                        </button>
                                    </form>
                                @else
                                    <button type="button" disabled class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-slate-700 border border-transparent rounded-md font-semibold text-xs text-gray-500 dark:text-slate-300 uppercase tracking-widest cursor-not-allowed">
                                        Rollback unavailable
                                    </button>
                                @endif
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
                                    <th scope="col" class="px-6 py-3 text-right">Actions</th>
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
                                    <td class="px-6 py-4 text-right">
                                        @if($history->is_current)
                                            <span class="text-xs text-gray-400 dark:text-slate-500">Current version</span>
                                        @elseif(($history->can_rollback ?? false) && $canApplyUpdates)
                                            <form action="{{ route('tenant.updates.rollback', $history->release->id) }}" method="POST" class="inline-flex js-update-action" data-action-label="Rolling back to {{ $history->release->version_tag }}" onsubmit="return confirm('A backup will be created before rollback. Continue?');">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-2 rounded-md border border-red-300 bg-red-50 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-red-700 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/30">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25 4.5 9.75m0 0L9 5.25m-4.5 4.5H16.5A2.25 2.25 0 0 1 18.75 12v6.75" />
                                                    </svg>
                                                    Rollback
                                                </button>
                                            </form>
                                        @elseif(!empty($history->rollback_errors ?? []))
                                            <span class="text-xs text-gray-400 dark:text-slate-500" title="{{ implode(' ', $history->rollback_errors) }}">
                                                {{ $history->rollback_errors[0] }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400 dark:text-slate-500">No actions</span>
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

    <div id="update-progress" class="fixed right-4 top-4 z-[90] hidden w-80 rounded-lg border border-blue-200 bg-white p-4 shadow-lg dark:border-blue-800 dark:bg-slate-900" role="status" aria-live="polite" aria-atomic="true">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p id="update-progress-title" class="text-sm font-semibold text-blue-700 dark:text-blue-300">Processing update</p>
                <p id="update-progress-message" class="mt-1 text-xs text-gray-600 dark:text-slate-300">Preparing backup and applying changes...</p>
            </div>
            <svg class="h-4 w-4 animate-spin text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
        </div>
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-blue-100 dark:bg-blue-900/40">
            <div class="h-full w-1/3 animate-pulse rounded-full bg-blue-600 dark:bg-blue-400"></div>
        </div>
    </div>

    <script>
        (() => {
            const forms = document.querySelectorAll('.js-update-action');
            const panel = document.getElementById('update-progress');
            const title = document.getElementById('update-progress-title');
            const message = document.getElementById('update-progress-message');

            if (!forms.length || !panel || !title || !message) {
                return;
            }

            forms.forEach((form) => {
                form.addEventListener('submit', () => {
                    const label = form.dataset.actionLabel || 'Processing update';
                    title.textContent = label;
                    message.textContent = 'This may take a minute. Please keep this page open.';
                    panel.classList.remove('hidden');

                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.classList.add('opacity-70', 'cursor-wait');
                        submitButton.dataset.originalText = submitButton.textContent.trim();
                        submitButton.textContent = 'Processing...';
                    }
                });
            });
        })();
    </script>
</x-tenant-layout>
