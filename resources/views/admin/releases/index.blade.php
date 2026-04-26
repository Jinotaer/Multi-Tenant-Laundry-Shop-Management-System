<x-admin-layout>
    <x-slot name="header">
        <x-admin-header title="App Releases" description="Track and manage platform version releases.">
            <x-slot name="actions">
                <form action="{{ route('admin.releases.sync') }}" method="POST">
                    @csrf
                    <button
                        type="submit"
                        class="tenant-primary-action inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-semibold shadow-sm"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Sync from GitHub
                    </button>
                </form>
            </x-slot>
        </x-admin-header>
    </x-slot>

    <div class="admin-page-stack space-y-6">
        @if (session('success'))
            <div class="tenant-alert border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="tenant-alert border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
                {{ session('error') }}
            </div>
        @endif

        <section class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Total Releases</p>
                <p class="mt-1.5 text-xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    {{ number_format($releases->total()) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Active Tenants</p>
                <p class="mt-1.5 text-xl font-black tracking-tight" style="color: var(--tenant-theme-accent);">
                    {{ number_format($totalTenants) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Latest Version</p>
                <p class="mt-1.5 text-xl font-black tracking-tight text-emerald-600 dark:text-emerald-300">
                    {{ $releases->first()?->version_tag ?? 'None' }}
                </p>
            </div>
        </section>

        <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white/92 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            @if ($releases->isEmpty())
                <div class="px-8 py-12 text-center">
                    <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-12 dark:border-slate-700">
                        <svg class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="mt-4 text-base font-semibold text-slate-900 dark:text-slate-100">No releases found.</p>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                            Click "Sync from GitHub" to fetch the latest releases.
                        </p>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-left dark:bg-slate-950/60">
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Version</th>
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Name</th>
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Published</th>
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Adoption</th>
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($releases as $release)
                                @php
                                    $percentage = $totalTenants > 0 ? ($release->active_tenants_count / $totalTenants) * 100 : 0;
                                @endphp
                                <tr class="border-t border-slate-200 transition hover:bg-slate-50/70 dark:border-slate-800 dark:hover:bg-slate-950/40">
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $release->version_tag }}</span>
                                            @if ($release->is_prerelease)
                                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-amber-700 dark:bg-amber-500/15 dark:text-amber-200">
                                                    Pre-release
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <span class="text-sm text-slate-600 dark:text-slate-300">{{ $release->name ?: 'Untitled' }}</span>
                                    </td>

                                    <td class="px-6 py-5">
                                        <span class="text-sm text-slate-600 dark:text-slate-300">
                                            {{ $release->published_at ? $release->published_at->format('M d, Y') : 'N/A' }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="w-40">
                                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                                <div
                                                    class="h-full rounded-full"
                                                    style="width: {{ $percentage }}%; background: var(--tenant-theme-accent);"
                                                ></div>
                                            </div>
                                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                                                {{ $release->active_tenants_count }} / {{ $totalTenants }}
                                                <span class="font-semibold">({{ round($percentage, 1) }}%)</span>
                                            </p>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5 text-right">
                                        <a
                                            href="{{ route('admin.releases.show', $release->id) }}"
                                            class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                        >
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-6 py-4 dark:border-slate-800">
                    {{ $releases->links() }}
                </div>
            @endif
        </section>
    </div>
</x-admin-layout>
