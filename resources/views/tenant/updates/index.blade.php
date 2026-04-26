<x-tenant-layout>
    <div class="tenant-page-stack space-y-5">
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
            <div class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        @if (isset($applyingUpdate) && $applyingUpdate)
            <div class="flex items-center justify-between gap-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-500/10">
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
            <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-500/10">
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">Code deployment notice</p>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">
                    Tenant update actions run backups and tenant migrations, but shared application code deployment is currently restricted.
                    New features require deploying the latest release code on the server.
                </p>
            </div>
        @endif

        @php $tenantCanDeployCode = config('updates.auto_deploy_code', false) && config('updates.allow_tenant_code_deploy', false); @endphp

        {{-- Current Version Hero Card --}}
        <div class="relative overflow-hidden rounded-3xl border border-slate-300 bg-gradient-to-br from-white to-blue-50/60 p-6 shadow-sm dark:border-slate-700 dark:from-slate-900 dark:to-blue-950/40">
            {{-- Decorative glow (single, subtle) --}}
            <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-blue-200/30 blur-3xl dark:bg-blue-500/10" aria-hidden="true"></div>

            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    {{-- Version badge --}}
                    <div class="relative shrink-0">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-lg shadow-blue-500/25 ring-4 ring-white dark:ring-slate-900">
                            <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                            </svg>
                        </div>
                        @if ($availableUpdates->isEmpty())
                            <span class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-900">
                                <svg class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                        @else
                            <span class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-amber-500 text-[10px] font-bold text-white ring-2 ring-white dark:ring-slate-900">
                                {{ $availableUpdates->count() > 9 ? '9+' : $availableUpdates->count() }}
                            </span>
                        @endif
                    </div>

                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Current Version</p>
                        <div class="mt-1 flex items-baseline gap-2">
                            <p class="text-3xl font-black tracking-tight text-slate-900 dark:text-slate-100">{{ $currentVersion }}</p>
                            @if ($availableUpdates->isEmpty())
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Latest
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Your application is running this version.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if ($availableUpdates->isEmpty())
                        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3.5 py-2 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Up to date
                        </span>
                    @else
                        <span class="relative inline-flex items-center gap-2 rounded-full bg-blue-50 px-3.5 py-2 text-sm font-semibold text-blue-700 ring-1 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/30">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-blue-500"></span>
                            </span>
                            {{ $availableUpdates->count() }} {{ Str::plural('update', $availableUpdates->count()) }} available
                        </span>
                    @endif

                    <button
                        onclick="window.location.reload()"
                        title="Refresh"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-600 shadow-sm transition hover:rotate-180 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                        style="transition: transform 0.5s ease, background-color 0.2s ease, color 0.2s ease;"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Available Updates entry point --}}
        @if ($availableUpdates->isNotEmpty())
            <a
                href="{{ route('tenant.updates.available') }}"
                class="group relative block overflow-hidden rounded-3xl border border-blue-300 bg-gradient-to-r from-blue-50 to-indigo-50 p-1 shadow-sm transition duration-200 ease-out hover:-translate-y-0.5 hover:border-blue-400 hover:shadow-md dark:border-blue-500/40 dark:from-blue-500/10 dark:to-indigo-500/10 dark:hover:border-blue-500/60"
            >
                {{-- Shimmer overlay on hover --}}
                <div class="pointer-events-none absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/40 to-transparent transition-transform duration-1000 group-hover:translate-x-full dark:via-white/10" aria-hidden="true"></div>

                <div class="relative flex flex-col items-start justify-between gap-4 rounded-[20px] bg-white/60 px-6 py-5 backdrop-blur-sm dark:bg-slate-900/40 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-md shadow-blue-500/30">
                                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                            </div>
                            <span class="absolute -right-1 -top-1 flex h-3 w-3">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                                <span class="relative inline-flex h-3 w-3 rounded-full bg-amber-500"></span>
                            </span>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-base font-bold text-blue-900 dark:text-blue-100">
                                    {{ $availableUpdates->count() }} {{ Str::plural('update', $availableUpdates->count()) }} available
                                </p>
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.14em] text-amber-800 dark:bg-amber-500/20 dark:text-amber-200">
                                    New
                                </span>
                            </div>
                            <p class="mt-1 flex flex-wrap items-center gap-x-1.5 text-xs text-slate-600 dark:text-slate-300">
                                <span class="text-slate-500 dark:text-slate-400">Latest:</span>
                                <span class="font-semibold text-blue-700 dark:text-blue-300">{{ $availableUpdates->first()->version_tag }}</span>
                                <span class="hidden h-1 w-1 rounded-full bg-slate-300 sm:inline-block dark:bg-slate-600" aria-hidden="true"></span>
                                <span class="text-slate-500 dark:text-slate-400">view release notes and install</span>
                            </p>
                        </div>
                    </div>

                    <span class="tenant-primary-action inline-flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm transition group-hover:gap-3 group-hover:shadow-md">
                        View Updates
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5l6 6m0 0l-6 6m6-6h-15" />
                        </svg>
                    </span>
                </div>
            </a>
        @endif

        {{-- Version History --}}
        <div class="overflow-hidden rounded-3xl border border-slate-300 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-950/70">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Version History</h3>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Your update history and current version status.</p>
                    </div>
                </div>
                @if ($updateHistory->isNotEmpty())
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        {{ $updateHistory->count() }} {{ Str::plural('entry', $updateHistory->count()) }}
                    </span>
                @endif
            </div>

            @if ($updateHistory->isEmpty())
                <div class="flex flex-col items-center justify-center py-14 text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800">
                        <svg class="h-7 w-7 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-200">No version history yet</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Your updates will appear here once applied.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 text-left dark:bg-slate-900/60">
                                <th class="px-6 py-3.5 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Version</th>
                                <th class="px-6 py-3.5 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Status</th>
                                <th class="px-6 py-3.5 text-right text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($updateHistory as $history)
                                @php
                                    $status = $history->status;
                                    $statusStyles = match ($status) {
                                        'updated' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30',
                                        'applying' => 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/30',
                                        'failed' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30',
                                        'cancelled' => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700',
                                        'rolled_back' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30',
                                        default => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700',
                                    };
                                    $statusDot = match ($status) {
                                        'updated' => 'bg-emerald-500',
                                        'applying' => 'bg-blue-500 animate-pulse',
                                        'failed' => 'bg-rose-500',
                                        'cancelled' => 'bg-slate-400',
                                        'rolled_back' => 'bg-amber-500',
                                        default => 'bg-slate-400',
                                    };
                                @endphp
                                <tr class="border-t border-slate-100 transition hover:bg-slate-50/70 dark:border-slate-800 dark:hover:bg-slate-900/40">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $history->is_current ? 'bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-sm shadow-blue-500/30' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25A2.25 2.25 0 0113.5 8.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $history->release->version_tag }}</span>
                                                    @if ($history->is_current)
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30">
                                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                            Active
                                                        </span>
                                                    @endif
                                                </div>
                                                @if ($history->release->name)
                                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $history->release->name }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusStyles }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        @if ($history->action_taken_at)
                                            <span class="text-sm text-slate-600 dark:text-slate-300">{{ $history->action_taken_at->format('M d, Y') }}</span>
                                            <span class="ml-1 text-xs text-slate-400 dark:text-slate-500">{{ $history->action_taken_at->format('H:i') }}</span>
                                        @else
                                            <span class="text-sm text-slate-400 dark:text-slate-500">Pending</span>
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

    {{-- Update progress toast --}}
    <div id="update-progress" class="fixed right-4 top-4 z-[90] hidden w-80 rounded-2xl border border-blue-300 bg-white p-4 shadow-lg dark:border-blue-800 dark:bg-slate-900" role="status" aria-live="polite" aria-atomic="true">
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
                    message.textContent = 'Staging the update. You will be redirected to the progress page shortly.';
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
