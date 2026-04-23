<x-tenant-layout>
    <div class="space-y-5">
        <x-tenant-header title="Update Center" description="View the latest platform updates and features." />
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

            @if(isset($applyingUpdate) && $applyingUpdate)
                <div class="rounded-lg bg-yellow-50 p-4 text-sm text-yellow-800 border border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-300 flex items-center justify-between gap-4">
                    <div>
                        <p class="font-semibold">Update in progress</p>
                        <p class="mt-1">An update to <strong>{{ $applyingUpdate->release->version_tag }}</strong> is currently applying. Check the status page to see progress.</p>
                    </div>
                    <a href="{{ route('tenant.updates.status', $applyingUpdate->release->id) }}"
                       class="flex-shrink-0 inline-flex items-center gap-1 px-3 py-1.5 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900/40 dark:hover:bg-yellow-900/60 border border-yellow-300 dark:border-yellow-700 rounded-md text-xs font-medium text-yellow-800 dark:text-yellow-300 transition">
                        View status &rarr;
                    </a>
                </div>
            @endif

            @if(!config('updates.auto_deploy_code', false) || !config('updates.allow_tenant_code_deploy', false))
                <div class="rounded-lg bg-amber-50 p-4 text-sm text-amber-800 border border-amber-200 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-300">
                    <p class="font-semibold">Code deployment notice</p>
                    <p class="mt-1">
                        Tenant update actions here run backups and tenant migrations, but shared application code deployment is currently restricted.
                        New feature screens and controllers (such as Promotions) require deploying the latest release code on this server.
                    </p>
                </div>
            @endif

            @php
                $tenantCanDeployCode = config('updates.auto_deploy_code', false) && config('updates.allow_tenant_code_deploy', false);
            @endphp

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
                        <form action="{{ route('tenant.updates.check') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-3 py-2 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 border border-blue-200 dark:border-blue-800 rounded-md font-medium text-xs text-blue-700 dark:text-blue-300 uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                Check for Updates
                            </button>
                        </form>
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
                                @if($tenantCanDeployCode)
                                    <form action="{{ route('tenant.updates.apply', $release->id) }}" method="POST" class="js-update-action" data-action-label="Updating to {{ $release->version_tag }}" data-confirm="A backup will be created before updating. This will restart the server briefly. Continue?">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                            </svg>
                                            Update Now
                                        </button>
                                    </form>
                                @else
                                    <button type="button" disabled class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-slate-700 border border-transparent rounded-md font-semibold text-xs text-gray-600 dark:text-slate-300 uppercase tracking-widest opacity-80 cursor-not-allowed">
                                        Manual Deploy Required
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
            const forms   = document.querySelectorAll('.js-update-action');
            const panel   = document.getElementById('update-progress');
            const title   = document.getElementById('update-progress-title');
            const message = document.getElementById('update-progress-message');

            if (!forms.length || !panel || !title || !message) return;

            const hidePanel = () => {
                panel.classList.add('hidden');
                forms.forEach((form) => {
                    const btn = form.querySelector('button[type="submit"]');
                    if (!btn) return;
                    btn.disabled = false;
                    btn.classList.remove('opacity-70', 'cursor-wait');
                    if (btn.dataset.originalText) {
                        btn.textContent = btn.dataset.originalText;
                        delete btn.dataset.originalText;
                    }
                });
            };

            // Hide spinner on bfcache restore (browser back/forward).
            window.addEventListener('pageshow', hidePanel);

            forms.forEach((form) => {
                form.addEventListener('submit', (event) => {
                    // Confirm in JS so we can call event.preventDefault() on
                    // cancel — inline onsubmit="return confirm()" does NOT set
                    // event.defaultPrevented, which would leave the spinner
                    // visible after the user clicks "Cancel".
                    const confirmMsg = form.dataset.confirm;
                    if (confirmMsg && !confirm(confirmMsg)) {
                        event.preventDefault();
                        return;
                    }

                    const label = form.dataset.actionLabel || 'Processing update';
                    title.textContent = label;
                    message.textContent = 'Staging the update — you will be redirected to the progress page shortly.';
                    panel.classList.remove('hidden');

                    const btn = form.querySelector('button[type="submit"]');
                    if (btn) {
                        btn.disabled = true;
                        btn.classList.add('opacity-70', 'cursor-wait');
                        btn.dataset.originalText = btn.textContent.trim();
                        btn.textContent = 'Starting…';
                    }
                });
            });
        })();
    </script>
</x-tenant-layout>
