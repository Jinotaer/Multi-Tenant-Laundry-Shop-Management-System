<x-admin-layout>
    @php
        $planBreakdownCollection = collect($planBreakdown ?? []);
        $activeShopShare = $totalTenants > 0 ? round(($activeWorkspaces / $totalTenants) * 100, 1) : 0;
        $revenueDirectionUp = $revenueDelta !== null && $revenueDelta >= 0;
        $chartStops = [];
        $chartOffset = 0;

        foreach ($planBreakdownCollection as $plan) {
            $nextOffset = min(100, $chartOffset + (float) $plan['percentage']);
            $chartStops[] = "{$plan['hex']} {$chartOffset}% {$nextOffset}%";
            $chartOffset = $nextOffset;
        }

        if ($chartOffset < 100) {
            $chartStops[] = "#dbe4f0 {$chartOffset}% 100%";
        }

        $planChartStyle = 'background: conic-gradient('.implode(', ', $chartStops).');';
    @endphp

    <style>
        .admin-dashboard-page .dashboard-hero {
            background:
                radial-gradient(circle at top right, var(--tenant-theme-accent-soft-strong) 0%, transparent 28%),
                linear-gradient(135deg, rgb(255 255 255 / 0.98) 0%, rgb(248 250 252 / 0.96) 100%);
        }

        .dark .admin-dashboard-page .dashboard-hero {
            background:
                radial-gradient(circle at top right, var(--tenant-theme-accent-soft-strong) 0%, transparent 30%),
                linear-gradient(135deg, rgb(15 23 42 / 0.98) 0%, rgb(2 6 23 / 0.96) 100%);
        }

        .admin-dashboard-page .dashboard-card {
            position: relative;
            overflow: hidden;
            transition: box-shadow var(--transition-base), transform var(--transition-base);
        }

        .admin-dashboard-page .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .admin-dashboard-page .dashboard-card::after {
            content: '';
            position: absolute;
            top: -3.5rem;
            right: -3.5rem;
            height: 7rem;
            width: 7rem;
            border-radius: 9999px;
            background: var(--tenant-theme-accent-soft);
            opacity: 0.7;
            pointer-events: none;
        }

        .admin-dashboard-page .dashboard-card > * {
            position: relative;
            z-index: 1;
        }

        .admin-dashboard-page .dashboard-donut {
            position: relative;
            height: 10rem;
            width: 10rem;
            border-radius: 9999px;
        }

        .admin-dashboard-page .dashboard-donut::after {
            content: '';
            position: absolute;
            inset: 1.5rem;
            border-radius: 9999px;
            background: rgb(255 255 255 / 0.96);
            box-shadow: inset 0 1px 6px rgb(15 23 42 / 0.06);
        }

        .dark .admin-dashboard-page .dashboard-donut::after {
            background: rgb(15 23 42 / 0.96);
        }

        .admin-dashboard-page .dashboard-timeline::before {
            content: '';
            position: absolute;
            top: 0.5rem;
            bottom: 0.5rem;
            left: 0.8125rem;
            width: 1px;
            background: rgb(203 213 225 / 0.8);
        }

        .dark .admin-dashboard-page .dashboard-timeline::before {
            background: rgb(51 65 85 / 0.9);
        }
    </style>

    <div class="admin-dashboard-page admin-page-stack space-y-6">
        <section class="dashboard-hero rounded-[28px] border border-slate-200 px-6 py-6 shadow-sm dark:border-slate-800 sm:px-8">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="min-w-0">
                    <div class="mb-3 flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                        <span>Admin</span>
                        <span>/</span>
                        <span class="text-slate-700 dark:text-slate-200">Dashboard</span>
                    </div>

                    <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">
                        Platform Overview
                    </h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                        Monitor revenue, registrations, plan adoption, and recent platform activity from a single admin view.
                    </p>

                    <div class="mt-5 flex flex-wrap gap-3">
                        <span class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-white dark:bg-slate-100 dark:text-slate-900">
                            {{ number_format($totalTenants) }} Shops
                        </span>
                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200">
                            {{ number_format($activeWorkspaces) }} Active
                        </span>
                        <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-blue-700 dark:bg-blue-500/15 dark:text-blue-200">
                            {{ number_format($trialWorkspaces) }} Trial
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a
                        href="{{ route('admin.monitoring.export') }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V4.5m0 12l-4.5-4.5m4.5 4.5l4.5-4.5M4.5 19.5h15" />
                        </svg>
                        Export Report
                    </a>

                    <a
                        href="{{ route('shop.register') }}"
                        class="tenant-primary-action inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        New Shop
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="tenant-panel dashboard-card p-5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                    Revenue This Month
                </p>
                <p class="mt-3 text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    PHP {{ number_format((float) $currentMonthRevenue, 2) }}
                </p>
                <div class="mt-4 flex items-center gap-2 text-sm">
                    @if ($revenueDelta !== null)
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $revenueDirectionUp ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200' : 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-200' }}">
                            <svg class="mr-1 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $revenueDirectionUp ? 'M16.5 7.5h-9m9 0l-3-3m3 3l-3 3' : 'M7.5 16.5h9m-9 0l3-3m-3 3l3 3' }}" />
                            </svg>
                            {{ number_format(abs($revenueDelta), 1) }}%
                        </span>
                        <span class="text-slate-500 dark:text-slate-400">vs last month</span>
                    @else
                        <span class="text-slate-500 dark:text-slate-400">No previous-month benchmark yet.</span>
                    @endif
                </div>
            </article>

            <article class="tenant-panel dashboard-card p-5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                    Active Shops
                </p>
                <p class="mt-3 text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    {{ number_format($activeWorkspaces) }}
                </p>

                <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                    <div class="flex h-full w-full">
                        @forelse ($planBreakdownCollection as $plan)
                            <div class="{{ $plan['bar_class'] }} h-full" style="width: {{ $plan['percentage'] }}%"></div>
                        @empty
                            <div class="h-full w-full" style="background: var(--tenant-theme-accent); opacity: 0.28;"></div>
                        @endforelse
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                    @forelse ($planBreakdownCollection as $plan)
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full {{ $plan['dot_class'] }}"></span>
                            {{ $plan['name'] }} {{ number_format($plan['percentage'], 1) }}%
                        </span>
                    @empty
                        <span>No plan breakdown available.</span>
                    @endforelse
                </div>
            </article>

            <article class="tenant-panel dashboard-card p-5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                    Pending Registrations
                </p>
                <p class="mt-3 text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    {{ number_format($pendingRegistrations) }}
                </p>
                <div class="mt-4">
                    <a
                        href="{{ route('admin.registrations.index') }}"
                        class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-white shadow-sm"
                        style="background: var(--tenant-theme-accent);"
                    >
                        Review Queue
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5l6 6m0 0l-6 6m6-6h-15" />
                        </svg>
                    </a>
                </div>
            </article>

            <article class="tenant-panel dashboard-card p-5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                    Attention Needed
                </p>
                <p class="mt-3 text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    {{ number_format($attentionRequired) }}
                </p>
                <div class="mt-4 space-y-1 text-sm text-slate-500 dark:text-slate-400">
                    <p>{{ number_format($paidWorkspaces) }} paid workspaces</p>
                    <p>{{ number_format($trialWorkspaces) }} on trial</p>
                </div>
            </article>
        </section>

        <section class="grid gap-6 lg:grid-cols-3">
            <div class="tenant-panel overflow-hidden lg:col-span-2">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Recent Shop Registrations</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Latest shop signups and approval activity.</p>
                    </div>

                    <a
                        href="{{ route('admin.registrations.index') }}"
                        class="inline-flex items-center gap-1 text-sm font-semibold"
                        style="color: var(--tenant-theme-accent);"
                    >
                        View All
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6" />
                        </svg>
                    </a>
                </div>

                @if ($recentRegistrations->isEmpty())
                    <div class="px-5 py-8 text-sm text-slate-500 dark:text-slate-400">
                        No registration activity found yet.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse text-left">
                            <thead>
                                <tr class="bg-slate-50 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:bg-slate-950/60 dark:text-slate-400">
                                    <th class="px-5 py-4">Shop Name</th>
                                    <th class="px-5 py-4">Plan</th>
                                    <th class="px-5 py-4">Date Joined</th>
                                    <th class="px-5 py-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm text-slate-700 dark:text-slate-200">
                                @foreach ($recentRegistrations as $registration)
                                    @php
                                        $shopInitials = collect(preg_split('/\s+/', trim($registration->shop_name)) ?: [])
                                            ->filter()
                                            ->take(2)
                                            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
                                            ->implode('');
                                        $shopInitials = $shopInitials !== '' ? $shopInitials : strtoupper(substr($registration->shop_name, 0, 1));
                                        $hasTenant = in_array($registration->subdomain, $recentRegistrationTenantIds, true);
                                        $actionUrl = $registration->isApproved() && $hasTenant
                                            ? route('admin.tenants.show', $registration->subdomain)
                                            : route('admin.registrations.index');
                                        $actionLabel = $registration->isApproved() && $hasTenant ? 'Open Shop' : 'Review';
                                        $statusClasses = match ($registration->status) {
                                            'approved' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200',
                                            'rejected' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-200',
                                            default => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-200',
                                        };
                                        $planLabel = $registration->subscriptionPlan?->name ?? 'No plan';
                                        $matchedPlan = $planBreakdownCollection->firstWhere('name', $planLabel);
                                        $planClasses = $matchedPlan['badge_class']
                                            ?? 'bg-slate-100 text-slate-700 dark:bg-slate-500/15 dark:text-slate-200';
                                    @endphp

                                    <tr class="border-t border-slate-200 transition hover:bg-slate-50/80 dark:border-slate-800 dark:hover:bg-slate-950/50">
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold text-white" style="background: linear-gradient(135deg, var(--tenant-theme-accent) 0%, var(--tenant-theme-accent-soft-strong) 100%);">
                                                    {{ $shopInitials }}
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="truncate font-semibold text-slate-900 dark:text-slate-100">{{ $registration->shop_name }}</p>
                                                    <div class="mt-1 flex flex-wrap items-center gap-2">
                                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-[0.16em] {{ $statusClasses }}">
                                                            {{ ucfirst($registration->status) }}
                                                        </span>
                                                        <span class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $registration->owner_email }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $planClasses }}">
                                                {{ $planLabel }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-slate-500 dark:text-slate-400">
                                            {{ $registration->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <a href="{{ $actionUrl }}" class="text-sm font-semibold hover:underline" style="color: var(--tenant-theme-accent);">
                                                {{ $actionLabel }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="admin-page-stack space-y-6">
                <div class="tenant-panel p-5">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Revenue by Plan</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Current shop distribution across active plans.</p>

                    <div class="mt-6 flex justify-center">
                        <div class="dashboard-donut" style="{{ $planChartStyle }}">
                            <div class="absolute inset-0 z-10 flex items-center justify-center">
                                <div class="relative z-20 flex flex-col items-center justify-center">
                                    <span class="text-xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                                        {{ $totalTenants > 0 ? number_format($activeShopShare, 1) : '0.0' }}%
                                    </span>
                                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                        Active
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        @forelse ($planBreakdownCollection as $plan)
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="h-3 w-3 rounded-full {{ $plan['dot_class'] }}"></span>
                                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $plan['name'] }}</span>
                                </div>
                                <span class="text-sm text-slate-500 dark:text-slate-400">
                                    {{ number_format($plan['percentage'], 1) }}% ({{ number_format($plan['count']) }})
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400">No approved shops to chart yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="tenant-panel p-5">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Platform Activity</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Recent registration and billing events.</p>

                    @if ($activityFeed->isEmpty())
                        <p class="mt-6 text-sm text-slate-500 dark:text-slate-400">No recent platform activity found.</p>
                    @else
                        <div class="dashboard-timeline relative mt-6 space-y-5">
                            @foreach ($activityFeed as $activity)
                                @php
                                    $toneClasses = match ($activity['tone']) {
                                        'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200',
                                        'red' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-200',
                                        'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-200',
                                        'blue' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-200',
                                        default => 'bg-slate-100 text-slate-700 dark:bg-slate-500/15 dark:text-slate-200',
                                    };
                                @endphp

                                <div class="relative flex gap-4">
                                    <div class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $toneClasses }}">
                                        @if ($activity['icon'] === 'check')
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                        @elseif ($activity['icon'] === 'x')
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        @elseif ($activity['icon'] === 'wallet')
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12V8.25A2.25 2.25 0 0018.75 6H5.25A2.25 2.25 0 003 8.25v7.5A2.25 2.25 0 005.25 18h13.5A2.25 2.25 0 0021 15.75V12zm0 0h-3.75a1.5 1.5 0 110-3H21v3z" />
                                            </svg>
                                        @elseif ($activity['icon'] === 'warning')
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zm8.25-.75L13.5 4.5c-.67-1.152-2.33-1.152-3 0L3.75 15.75c-.67 1.152.16 2.625 1.5 2.625h13.5c1.34 0 2.17-1.473 1.5-2.625z" />
                                            </svg>
                                        @else
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2.25M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @endif
                                    </div>

                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $activity['title'] }}</p>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $activity['description'] }}</p>
                                        <p class="mt-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">
                                            {{ $activity['timestamp']->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>
</x-admin-layout>
