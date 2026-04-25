@if ($availableUpdates->isNotEmpty())
    @php
        $latestRelease = $availableUpdates->first();
        $olderReleases = $availableUpdates->slice(1);
    @endphp

    {{-- Latest Update (featured) --}}
    <div class="overflow-hidden rounded-[28px] border border-blue-200 bg-gradient-to-br from-blue-50 to-white shadow-sm dark:border-blue-500/30 dark:from-blue-500/10 dark:to-slate-900">
        <div class="flex items-center justify-between gap-3 border-b border-blue-200 px-6 py-4 dark:border-blue-500/30">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <h3 class="text-base font-bold text-blue-900 dark:text-blue-100">Latest Update</h3>
            </div>
            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-blue-700 dark:bg-blue-500/20 dark:text-blue-200">
                Recommended
            </span>
        </div>

        <div class="p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xl font-black tracking-tight text-slate-900 dark:text-slate-100">{{ $latestRelease->version_tag }}</span>
                        @if ($latestRelease->is_prerelease)
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-amber-700 dark:bg-amber-500/15 dark:text-amber-200">
                                Pre-release
                            </span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-300">{{ $latestRelease->name }}</p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Published {{ $latestRelease->published_at->diffForHumans() }}</p>
                    <div class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300 prose prose-sm dark:prose-invert max-w-none">
                        {!! Str::markdown($latestRelease->body ?? 'No release notes available.') !!}
                    </div>
                </div>

                <div class="shrink-0">
                    @if ($tenantCanDeployCode)
                        <form
                            action="{{ route('tenant.updates.apply', $latestRelease->id) }}"
                            method="POST"
                            class="js-update-action"
                            data-action-label="Updating to {{ $latestRelease->version_tag }}"
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
    </div>

    {{-- Previous Available Updates (table) --}}
    @if ($olderReleases->isNotEmpty())
        <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Previous Updates</h3>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Older releases you can still install directly.</p>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400 dark:bg-slate-500"></span>
                    {{ $olderReleases->count() }} {{ Str::plural('release', $olderReleases->count()) }}
                </span>
            </div>

            {{-- Table (≥sm) --}}
            <div class="hidden sm:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50/70 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:bg-slate-900/50 dark:text-slate-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Version</th>
                            <th scope="col" class="px-4 py-3">Release Notes</th>
                            <th scope="col" class="px-4 py-3">Published</th>
                            <th scope="col" class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($olderReleases as $release)
                            <tr class="group transition hover:bg-slate-50/60 dark:hover:bg-slate-900/40">
                                <td class="px-6 py-4 align-middle">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2.5 py-1 font-mono text-xs font-bold text-slate-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                                            {{ $release->version_tag }}
                                        </span>
                                        @if ($release->is_prerelease)
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-amber-700 dark:bg-amber-500/15 dark:text-amber-200">
                                                Pre-release
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-middle">
                                    <p class="truncate text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $release->name }}</p>
                                </td>
                                <td class="px-4 py-4 align-middle">
                                    <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0V11.25A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                        </svg>
                                        <span title="{{ $release->published_at->toDayDateTimeString() }}">{{ $release->published_at->diffForHumans() }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-middle text-right">
                                    @if ($tenantCanDeployCode)
                                        <form
                                            action="{{ route('tenant.updates.apply', $release->id) }}"
                                            method="POST"
                                            class="js-update-action inline-block"
                                            data-action-label="Updating to {{ $release->version_tag }}"
                                            data-confirm="A backup will be created before updating. This will restart the server briefly. Continue?"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-500/40 dark:hover:bg-blue-500/10 dark:hover:text-blue-200"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                </svg>
                                                Install
                                            </button>
                                        </form>
                                    @else
                                        <span class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                            Manual Deploy
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards (<sm) --}}
            <ul class="divide-y divide-slate-200 dark:divide-slate-800 sm:hidden">
                @foreach ($olderReleases as $release)
                    <li class="flex flex-col gap-3 px-6 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2 py-0.5 font-mono text-xs font-bold text-slate-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                                        {{ $release->version_tag }}
                                    </span>
                                    @if ($release->is_prerelease)
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-amber-700 dark:bg-amber-500/15 dark:text-amber-200">
                                            Pre-release
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $release->name }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-400 dark:text-slate-500">Published {{ $release->published_at->diffForHumans() }}</p>
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
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-500/40 dark:hover:bg-blue-500/10 dark:hover:text-blue-200"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                            </svg>
                                            Install
                                        </button>
                                    </form>
                                @else
                                    <span class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                        Manual Deploy
                                    </span>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endif
