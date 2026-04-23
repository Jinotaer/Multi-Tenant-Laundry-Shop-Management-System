<x-admin-layout>
    @php
        $featureLibraryCount = $plans
            ->flatMap(fn ($plan) => $plan->features ?? [])
            ->filter()
            ->unique()
            ->count();
    @endphp

    <style>
        .subscription-plans-page .plan-card {
            position: relative;
            overflow: hidden;
            background: rgb(255 255 255 / 0.92);
        }

        .dark .subscription-plans-page .plan-card {
            background: rgb(15 23 42 / 0.92);
        }

        .subscription-plans-page .plan-card::after {
            content: '';
            position: absolute;
            top: -4rem;
            right: -4rem;
            height: 8rem;
            width: 8rem;
            border-radius: 9999px;
            background: var(--tenant-theme-accent-soft);
            opacity: 0.7;
            pointer-events: none;
        }

        .subscription-plans-page .plan-card > * {
            position: relative;
            z-index: 1;
        }
    </style>

    <div class="subscription-plans-page space-y-6">
        @if (session('success'))
            <div class="tenant-alert border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="tenant-alert border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
                {{ session('error') }}
            </div>
        @endif

        <x-admin-header title="Subscription Plans" description="Manage and configure subscription plans for shops.">
            <x-slot name="actions">
                <a
                    href="{{ route('admin.subscription-plans.create') }}"
                    class="tenant-primary-action inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-semibold shadow-sm"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Create Plan
                </a>
            </x-slot>
        </x-admin-header>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Total Plans</p>
                <p class="mt-1.5 text-xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    {{ number_format($planSummary['total'] ?? 0) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Active Plans</p>
                <p class="mt-1.5 text-xl font-black tracking-tight text-emerald-600 dark:text-emerald-300">
                    {{ number_format($planSummary['active'] ?? 0) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Assigned Shops</p>
                <p class="mt-1.5 text-xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    {{ number_format($planSummary['assigned_shops'] ?? 0) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Feature Library</p>
                <p class="mt-1.5 text-xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    {{ number_format($featureLibraryCount) }}
                </p>
            </div>
        </section>

        @if ($plans->isEmpty())
            <section class="rounded-[28px] border border-slate-200 bg-white/92 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/92 sm:p-8">
                <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-12 text-center dark:border-slate-700">
                    <svg class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6.75h16.5M3.75 17.25h16.5M6.75 3.75h10.5A2.25 2.25 0 0119.5 6v12a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 18V6a2.25 2.25 0 012.25-2.25z" />
                    </svg>
                    <p class="mt-4 text-base font-semibold text-slate-900 dark:text-slate-100">No subscription plans yet.</p>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Create your first pricing plan to start assigning shops to tiers.
                    </p>
                    <div class="mt-6">
                        <a
                            href="{{ route('admin.subscription-plans.create') }}"
                            class="tenant-primary-action inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Create Plan
                        </a>
                    </div>
                </div>
            </section>
        @else
            <section class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                @foreach ($plans as $plan)
                    @php
                        $featureLabels = collect($plan->features ?? [])
                            ->map(fn ($feature) => config("themes.features.{$feature}.label", ucwords(str_replace('_', ' ', $feature))))
                            ->values();

                        $billingCycleLabel = match ($plan->billing_cycle) {
                            'monthly' => 'month',
                            'yearly' => 'year',
                            default => strtolower((string) $plan->billing_cycle),
                        };

                        $statusTone = match (true) {
                            ! $plan->is_active => [
                                'icon' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300',
                                'strip' => 'from-slate-400 to-slate-500',
                            ],
                            $plan->is_default => [
                                'icon' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200',
                                'strip' => 'from-emerald-400 to-teal-500',
                            ],
                            $plan->isFree() => [
                                'icon' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-200',
                                'strip' => 'from-blue-400 to-indigo-500',
                            ],
                            default => [
                                'icon' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-200',
                                'strip' => 'from-indigo-400 to-violet-500',
                            ],
                        };
                    @endphp

                    <article class="plan-card flex h-full flex-col rounded-[28px] border border-slate-200 shadow-sm dark:border-slate-800 {{ ! $plan->is_active ? 'opacity-80' : '' }}">
                        <div class="h-1.5 w-full bg-gradient-to-r {{ $statusTone['strip'] }}"></div>

                        <div class="flex flex-1 flex-col p-5 sm:p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap gap-2">
                                        @if ($plan->is_default)
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200">
                                                Default
                                            </span>
                                        @endif

                                        @if (! $plan->is_active)
                                            <span class="inline-flex items-center rounded-full bg-slate-200 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                Inactive
                                            </span>
                                        @endif
                                    </div>

                                    <h2 class="mt-4 text-lg font-black tracking-tight text-slate-900 dark:text-slate-100">
                                        {{ $plan->name }}
                                    </h2>

                                    <div class="mt-3 flex flex-wrap items-end gap-2">
                                        @if ($plan->isFree())
                                            <span class="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">Free</span>
                                        @else
                                            <span class="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                                                PHP {{ number_format((float) $plan->price, 0) }}
                                            </span>
                                            <span class="pb-1 text-xs text-slate-500 dark:text-slate-400">/ {{ $billingCycleLabel }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $statusTone['icon'] }}">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 6.75A2.25 2.25 0 016.75 4.5h10.5a2.25 2.25 0 012.25 2.25v10.5A2.25 2.25 0 0117.25 19.5H6.75A2.25 2.25 0 014.5 17.25V6.75z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 9.75h7.5M8.25 13.5h4.5" />
                                    </svg>
                                </div>
                            </div>

                            @if ($plan->description)
                                <p class="mt-3 text-xs leading-6 text-slate-600 dark:text-slate-300">
                                    {{ $plan->description }}
                                </p>
                            @endif

                            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-950/50">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Staff</p>
                                    <p class="mt-1.5 text-xs font-semibold text-slate-900 dark:text-slate-100">{{ $plan->staff_limit_display }}</p>
                                </div>

                                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-950/50">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Customers</p>
                                    <p class="mt-1.5 text-xs font-semibold text-slate-900 dark:text-slate-100">{{ $plan->customer_limit_display }}</p>
                                </div>

                                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-950/50">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Orders / Month</p>
                                    <p class="mt-1.5 text-xs font-semibold text-slate-900 dark:text-slate-100">{{ $plan->order_limit_display }}</p>
                                </div>
                            </div>

                            <div class="mt-5 border-t border-slate-200 pt-4 dark:border-slate-800">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Features</p>

                                @if ($featureLabels->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($featureLabels as $label)
                                            <span class="inline-flex items-center rounded-lg bg-indigo-50 px-2 py-0.5 text-[11px] font-medium text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-200">
                                                {{ $label }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-6">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-900 dark:text-slate-100">
                                        {{ number_format($plan->tenants_count) }} shop(s) on this plan
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <a
                                        href="{{ route('admin.subscription-plans.edit', $plan) }}"
                                        class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                    >
                                        Edit Plan
                                    </a>

                                    @if ($plan->tenants_count === 0)
                                        <form method="POST" action="{{ route('admin.subscription-plans.destroy', $plan) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-xl bg-rose-600 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-rose-700"
                                                onclick="return confirm('Delete this plan? This cannot be undone.')"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500">
                                            In use - cannot delete
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
</x-admin-layout>
