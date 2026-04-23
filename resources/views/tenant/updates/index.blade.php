<x-tenant-layout>
    <div class="space-y-5">
        <x-tenant-header title="Update Center" description="View the latest platform updates and features.">
            <x-slot name="actions">
                <form action="{{ route('tenant.updates.check') }}" method="POST" class="inline">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Check for Updates
                    </button>
                </form>
            </x-slot>
        </x-tenant-header>

        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        @if (isset($applyingUpdate) && $applyingUpdate)
            <div class="flex items-center justify-between gap-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-500/10">
                <div>
                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">Update in progress</p>
                    <p class="mt-0.5 text-sm text-amber-700 dark:text-amber-400">
                        Applying <strong>{{ $applyingUpdate->release->version_tag }}</strong>. Check the status page for progress.
                    </p>
                </div>
                <a
                    href="{{ route('tenant.updates.status', $applyingUpdate->release->id) }}"
                    class="shrink-0 inline-flex items-center gap-1.5 rounded-xl border border-amber-300 bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-800 transition hover:bg-amber-200 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-300"
                >
                    View Status
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5l6 6m0 0l-6 6m6-6h-15" />
                    </svg>
                </a>
            </div>
        @endif

        @if (!config('updates.auto_deploy_code', false) || !config('updates.allow_tenant_code_deploy', false))
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-500/10">
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">Code deployment notice</p>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">
                    Tenant update actions run backups and tenant migrations, but shared application code deployment is currently restricted.
                    New features require deploying the latest release code on the server.
                </p>
            </div>
        @endif

        @php $tenantCanDeployCode = config('updates.auto_deploy_code', false) && config('updates.allow_tenant_code_deploy', false); @endphp

        {{-- Current Version Card --}}
        <div class="tenant-panel p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Current Version</p>
                    <p class="mt-1.5 text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">{{ $currentVersion }}</p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Your application is running this version.</p>
                </div>

                <div class="flex items-center gap-3">
                    @if ($availableUpdates->isEmpty())
                        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1.5 text-sm font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Up to date
                        </span>
                    @else
                        <span class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1.5 text-sm font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            {{ $availableUpdates->count() }} {{ Str::plural('update', $availableUpdates->count()) }} available
                        </span>
                    @endif

                    <button
                        onclick="window.location.reload()"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
        </div>

        {{-- Available Updates --}}
        @if ($availableUpdates->isNotEmpty())
            <div class="overflow-hidden rounded-[28px] border border-blue-200 bg-blue-50/60 shadow-sm dark:border-blue-500/30 dark:bg-blue-500/10">
                <div class="flex items-center gap-2 border-b border-blue-200 px-6 py-4 dark:border-blue-500/30">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <h3 class="text-base font-bold text-blue-900 dark:text-blue-100">Available Updates</h3>
                </div>

                <div class="space-y-3 p-5">
                    @foreach ($availableUpdates as $release)
                        <div class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-base font-black tracking-tight text-slate-900 dark:text-slate-100">{{ $release->version_tag }}</span>
                                        @if ($release->is_prerelease)
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-amber-700 dark:bg-amber-500/15 dark:text-amber-200">
                                                Pre-release
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-300">{{ $release->name }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Published {{ $release->published_at->diffForHumans() }}</p>
                                    <div class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300 prose prose-sm dark:prose-invert max-w-none">
                                        {!! Str::markdown($release->body ?? 'No release notes available.') !!}
                                    </div>
                                </div>

                                <div class="shrink-0">
                                    @if ($tenantCanDeployCode)
                                        <form
                                            action="{{ route('tenant.updates.apply', $release->id) }}"
                                            method="POST"
                                            class="js-update-action"
                                            data-action-label="Updating to {{ $release->version_tag }}"
                                            data-confirm="A backup will be created before updating. This will restart the server briefly. Continue?"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="tenant-primary-action inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                </svg>
                                                Update Now
                                            </button>
                                        </form>
                                    @else
                                        <span class="inline-flex items-center rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                            Manual Deploy Required
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Version History --}}
        <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white/92 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Version History</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Your update history and current version status.</p>
            </div>

            @if ($updateHistory->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <svg class="h-10 w-10 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">No version history available yet.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-left dark:bg-slate-950/60">
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Version</th>
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Status</th>
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($updateHistory as $history)
                                <tr class="border-t border-slate-200 transition hover:bg-slate-50/70 dark:border-slate-800 dark:hover:bg-slate-950/40">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $history->release->version_tag }}</span>
                                            @if ($history->is_current)
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                                    Active
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">
                                        {{ ucfirst(str_replace('_', ' ', $history->status)) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                                        {{ $history->action_taken_at ? $history->action_taken_at->format('M d, Y H:i') : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Update progress toast --}}
    <div id="update-progress" class="fixed right-4 top-4 z-[90] hidden w-80 rounded-2xl border border-blue-200 bg-white p-4 shadow-lg dark:border-blue-800 dark:bg-slate-900" role="status" aria-live="polite" aria-atomic="true">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p id="update-progress-title" class="text-sm font-semibold text-blue-700 dark:text-blue-300">Processing update</p>
                <p id="update-progress-message" class="mt-1 text-xs text-slate-600 dark:text-slate-300">Preparing backup and applying changes…</p>
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

            window.addEventListener('pageshow', hidePanel);

            forms.forEach((form) => {
                form.addEventListener('submit', (event) => {
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
