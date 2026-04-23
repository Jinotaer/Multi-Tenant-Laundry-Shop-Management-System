<x-admin-layout>
    @php
        $shopName = $tenant->data['shop_name'] ?? $tenant->registration?->shop_name ?? $tenant->id;
        $primaryDomain = $tenant->domains->first();
        $portalUrl = $primaryDomain ? "http://{$primaryDomain->domain}:8000" : null;
        $subscriptionPlan = $tenant->subscriptionPlan;
        $renewalDate = $tenant->subscriptionRenewsAt();
        $storageUsage = $tenant->getStorageUsagePercentage();
        $bandwidthUsage = $tenant->getBandwidthUsagePercentage();
        $storageBarWidth = $storageUsage === null ? 38 : (int) round(max(0, min(100, $storageUsage)));
        $bandwidthBarWidth = $bandwidthUsage === null ? 38 : (int) round(max(0, min(100, $bandwidthUsage)));
        $enabledFeatures = $tenant->features ?? [];
        $featureCatalog = config('themes.features', []);
        $additionalData = collect($tenant->data ?? [])->except(['shop_name']);
        $ownerName = $tenant->registration?->owner_name ?? 'Owner not available';
        $ownerEmail = $tenant->registration?->owner_email;
        $ownerInitials = collect(
            preg_split('/\s+/', trim($tenant->registration?->owner_name ?: $shopName)) ?: []
        )
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
            ->implode('');
        $ownerInitials = $ownerInitials !== '' ? $ownerInitials : 'NA';
        $billingCycleLabel = match ($subscriptionPlan?->billing_cycle) {
            'monthly' => 'month',
            'yearly' => 'year',
            default => $subscriptionPlan?->billing_cycle,
        };
        $planPriceLabel = match (true) {
            ! $subscriptionPlan => 'No active plan',
            $subscriptionPlan->isFree() => 'Free plan',
            default => 'PHP '.number_format((float) $subscriptionPlan->price, 2).' / '.$billingCycleLabel,
        };

        if ($tenant->is_paid) {
            $billingLabel = 'Paid';
            $billingClasses = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200';
            $billingSummary = $renewalDate
                ? 'Renews on '.$renewalDate->format('M d, Y')
                : 'Paid subscription is active.';
        } elseif ($tenant->isOnTrial()) {
            $billingLabel = 'Trial';
            $billingClasses = 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-200';
            $billingSummary = 'Trial ends in '.$tenant->trialDaysRemaining().' '.
                \Illuminate\Support\Str::plural('day', $tenant->trialDaysRemaining()).'.';
        } elseif ($tenant->isInGracePeriod()) {
            $billingLabel = 'Grace Period';
            $billingClasses = 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-200';
            $billingSummary = 'Renew within '.$tenant->graceDaysRemaining().' '.
                \Illuminate\Support\Str::plural('day', $tenant->graceDaysRemaining()).'.';
        } elseif ($tenant->isSubscriptionExpired() || $tenant->isTrialExpired()) {
            $billingLabel = 'Expired';
            $billingClasses = 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-200';
            $billingSummary = 'Access requires renewal or admin action.';
        } else {
            $billingLabel = 'Unassigned';
            $billingClasses = 'bg-slate-100 text-slate-700 dark:bg-slate-500/15 dark:text-slate-200';
            $billingSummary = 'No trial or paid subscription is active.';
        }
    @endphp

    <style>
        .shop-show-page .shop-hero {
            background:
                radial-gradient(circle at top right, var(--tenant-theme-accent-soft-strong) 0%, transparent 32%),
                linear-gradient(135deg, rgb(255 255 255 / 0.98) 0%, rgb(248 250 252 / 0.96) 100%);
        }

        .dark .shop-show-page .shop-hero {
            background:
                radial-gradient(circle at top right, var(--tenant-theme-accent-soft-strong) 0%, transparent 34%),
                linear-gradient(135deg, rgb(15 23 42 / 0.98) 0%, rgb(2 6 23 / 0.96) 100%);
        }

        .shop-show-page .shop-card {
            position: relative;
            overflow: hidden;
        }

        .shop-show-page .shop-card::after {
            content: '';
            position: absolute;
            right: -4.5rem;
            top: -4.5rem;
            height: 9rem;
            width: 9rem;
            border-radius: 9999px;
            background: var(--tenant-theme-accent-soft);
            opacity: 0.75;
            pointer-events: none;
        }

        .shop-show-page .shop-stat-card {
            background: rgb(248 250 252 / 0.84);
        }

        .dark .shop-show-page .shop-stat-card {
            background: rgb(2 6 23 / 0.55);
        }

        .shop-show-page .shop-progress-fill {
            background: linear-gradient(
                90deg,
                var(--tenant-theme-accent) 0%,
                var(--tenant-theme-accent-soft-strong) 100%
            );
        }

        .shop-show-page .feature-switch {
            position: relative;
            display: inline-block;
            height: 1.5rem;
            width: 2.75rem;
            border-radius: 9999px;
            background: rgb(203 213 225 / 1);
            transition: background-color 160ms ease;
        }

        .dark .shop-show-page .feature-switch {
            background: rgb(51 65 85 / 1);
        }

        .shop-show-page .feature-switch::after {
            content: '';
            position: absolute;
            top: 0.125rem;
            left: 0.125rem;
            height: 1.25rem;
            width: 1.25rem;
            border-radius: 9999px;
            background: #fff;
            box-shadow: 0 1px 2px rgb(15 23 42 / 0.18);
            transition: transform 160ms ease;
        }

        .shop-show-page .feature-toggle:checked + .feature-switch {
            background: var(--tenant-theme-accent);
        }

        .shop-show-page .feature-toggle:checked + .feature-switch::after {
            transform: translateX(1.25rem);
        }
    </style>

    <div class="shop-show-page space-y-6">
        @if (session('error'))
            <div class="tenant-alert border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="tenant-alert border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
                <p class="font-semibold">Please review the form errors below.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="shop-hero rounded-[28px] border border-slate-200 px-6 py-6 shadow-sm dark:border-slate-800 sm:px-8">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="min-w-0">
                    <div class="mb-3 flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                        <a href="{{ route('admin.tenants.index') }}" class="transition hover:text-slate-900 dark:hover:text-slate-100">Shops</a>
                        <span>/</span>
                        <span class="truncate text-slate-700 dark:text-slate-200">{{ $shopName }}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">
                            {{ $shopName }}
                        </h1>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] {{ $tenant->isEnabled() ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200' : 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-200' }}">
                            {{ $tenant->isEnabled() ? 'Active' : 'Disabled' }}
                        </span>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] {{ $billingClasses }}">
                            {{ $billingLabel }}
                        </span>
                    </div>

                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                        {{ $billingSummary }}
                        @if ($tenant->isDisabled())
                            Users cannot access the portal until the shop is enabled again.
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    @if ($portalUrl)
                        <a
                            href="{{ $portalUrl }}"
                            target="_blank"
                            rel="noreferrer"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200 dark:hover:bg-slate-800"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                            Access Portal
                        </a>
                    @endif

                    <form method="POST" action="{{ route('admin.tenants.toggle-status', $tenant) }}">
                        @csrf
                        @method('PATCH')

                        <button
                            type="submit"
                            onclick="return confirm('{{ $tenant->isEnabled() ? 'Disable this shop? Users will not be able to access it.' : 'Enable this shop?' }}')"
                            class="tenant-primary-action inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tenant->isEnabled() ? 'M18 12H6' : 'M12 6v12m6-6H6' }}" />
                            </svg>
                            {{ $tenant->isEnabled() ? 'Disable Shop' : 'Enable Shop' }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="shop-stat-card rounded-2xl border border-slate-200/80 p-4 dark:border-slate-800">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Tenant ID</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $tenant->id }}</p>
                </div>

                <div class="shop-stat-card rounded-2xl border border-slate-200/80 p-4 dark:border-slate-800">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Primary Domain</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                        {{ $primaryDomain?->domain ?? 'No domain configured' }}
                    </p>
                </div>

                <div class="shop-stat-card rounded-2xl border border-slate-200/80 p-4 dark:border-slate-800">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Current Plan</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                        {{ $subscriptionPlan?->name ?? 'No plan assigned' }}
                    </p>
                </div>

                <div class="shop-stat-card rounded-2xl border border-slate-200/80 p-4 dark:border-slate-800">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Release</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $tenant->currentVersion() }}</p>
                </div>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="tenant-panel shop-card p-6 xl:col-span-2">
                <div class="relative z-10">
                    <div class="mb-6 flex items-center justify-between border-b border-slate-200 pb-4 dark:border-slate-800">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Shop Overview</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Core identity, access, and lifecycle details.</p>
                        </div>
                    </div>

                    <dl class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Shop Name</dt>
                            <dd class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $shopName }}</dd>
                        </div>

                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Shop Code</dt>
                            <dd class="mt-2 inline-flex rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-sm font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                {{ strtoupper($tenant->id) }}
                            </dd>
                        </div>

                        <div class="sm:col-span-2">
                            <dt class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Domains</dt>
                            <dd class="mt-2 flex flex-wrap gap-2">
                                @forelse ($tenant->domains as $domain)
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                        {{ $domain->domain }}
                                    </span>
                                @empty
                                    <span class="text-sm text-slate-500 dark:text-slate-400">No domains configured.</span>
                                @endforelse
                            </dd>
                        </div>

                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Registration Status</dt>
                            <dd class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                                {{ $tenant->registration?->status ? ucfirst($tenant->registration->status) : 'N/A' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Approved Date</dt>
                            <dd class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                                {{ $tenant->registration?->approved_at?->format('M d, Y') ?? 'N/A' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Created Date</dt>
                            <dd class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $tenant->created_at->format('M d, Y h:i A') }}</dd>
                        </div>

                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Subscription Expiry</dt>
                            <dd class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                                {{ $tenant->subscription_expires_at?->format('M d, Y') ?? 'Not set' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="tenant-panel shop-card p-6">
                <div class="relative z-10">
                    <div class="mb-6 flex items-center justify-between border-b border-slate-200 pb-4 dark:border-slate-800">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Owner Contact</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Registration contact and access owner.</p>
                        </div>
                    </div>

                    <div class="mb-6 flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl text-base font-black text-white shadow-sm" style="background: linear-gradient(135deg, var(--tenant-theme-accent) 0%, var(--tenant-theme-accent-soft-strong) 100%);">
                            {{ $ownerInitials }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-base font-semibold text-slate-900 dark:text-slate-100">{{ $ownerName }}</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Primary owner</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5A2.25 2.25 0 0119.5 19.5h-15A2.25 2.25 0 012.25 17.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15A2.25 2.25 0 002.25 6.75m19.5 0v.243a2.25 2.25 0 01-1.07 1.918l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.911A2.25 2.25 0 012.25 6.993V6.75" />
                            </svg>
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Email</p>
                                @if ($ownerEmail)
                                    <a href="mailto:{{ $ownerEmail }}" class="mt-1 block truncate text-sm font-semibold text-slate-900 transition hover:underline dark:text-slate-100">
                                        {{ $ownerEmail }}
                                    </a>
                                @else
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">No email available.</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5M3.75 9.75h16.5M3.75 14.25h10.5m-10.5 4.5h10.5" />
                            </svg>
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Subdomain</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">
                                    {{ $tenant->registration?->subdomain ?? $tenant->id }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z" />
                            </svg>
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Registered</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">
                                    {{ $tenant->registration?->created_at?->format('M d, Y h:i A') ?? 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <section class="tenant-panel shop-card p-6">
            <div class="relative z-10">
                <div class="mb-6 flex items-center justify-between border-b border-slate-200 pb-4 dark:border-slate-800">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Subscription and Billing</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Plan details, usage, and billing controls for this shop.</p>
                    </div>
                </div>

                <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_minmax(0,0.95fr)]">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-6 dark:border-slate-800 dark:bg-slate-950/50">
                        <div class="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <div class="mb-3 inline-flex rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] {{ $billingClasses }}">
                                    {{ $billingLabel }}
                                </div>
                                <h3 class="text-xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                                    {{ $subscriptionPlan?->name ?? 'No plan assigned' }}
                                </h3>
                                <p class="mt-2 text-base text-slate-600 dark:text-slate-300">{{ $planPriceLabel }}</p>
                            </div>

                            @if ($subscriptionPlan?->is_default)
                                <span class="inline-flex rounded-full bg-slate-900 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-white dark:bg-slate-100 dark:text-slate-900">
                                    Default
                                </span>
                            @endif
                        </div>

                        <div class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                            <div class="flex items-center justify-between gap-4">
                                <span>Next billing date</span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">
                                    {{ $renewalDate?->format('M d, Y') ?? 'Not available' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span>Staff limit</span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $subscriptionPlan?->staff_limit_display ?? 'N/A' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span>Customer limit</span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $subscriptionPlan?->customer_limit_display ?? 'N/A' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span>Order limit</span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $subscriptionPlan?->order_limit_display ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-500 dark:text-slate-400">Storage Usage</span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">
                                    {{ $tenant->formatted_current_storage }} / {{ $tenant->formatted_storage_limit }}
                                </span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-200 dark:bg-slate-800">
                                <div class="shop-progress-fill h-2 rounded-full {{ $storageUsage === null ? 'opacity-45' : '' }}" style="width: {{ $storageBarWidth }}%;"></div>
                            </div>
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                {{ $storageUsage === null ? 'No storage cap is set for this shop.' : number_format($storageUsage, 1).'% used' }}
                            </p>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-500 dark:text-slate-400">Bandwidth Usage</span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">
                                    {{ $tenant->formatted_current_bandwidth }} / {{ $tenant->formatted_bandwidth_limit }}
                                </span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-200 dark:bg-slate-800">
                                <div class="shop-progress-fill h-2 rounded-full {{ $bandwidthUsage === null ? 'opacity-45' : '' }}" style="width: {{ $bandwidthBarWidth }}%;"></div>
                            </div>
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                {{ $bandwidthUsage === null ? 'No bandwidth cap is set for this shop.' : number_format($bandwidthUsage, 1).'% used' }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-950/50">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">API Requests</p>
                            <p class="mt-2 text-xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                                {{ number_format((int) $tenant->current_api_requests) }}
                            </p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Current recorded request volume.</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <form method="POST" action="{{ route('admin.tenants.update-plan', $tenant) }}" class="space-y-3 rounded-2xl border border-slate-200 bg-white/70 p-4 dark:border-slate-800 dark:bg-slate-900/70">
                            @csrf
                            @method('PATCH')

                            <div>
                                <label for="subscription_plan_id" class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                                    Change Plan
                                </label>
                                <select
                                    id="subscription_plan_id"
                                    name="subscription_plan_id"
                                    class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                                >
                                    @foreach ($plans as $plan)
                                        @php
                                            $cycleLabel = match ($plan->billing_cycle) {
                                                'monthly' => 'month',
                                                'yearly' => 'year',
                                                default => $plan->billing_cycle,
                                            };
                                        @endphp
                                        <option value="{{ $plan->id }}" @selected($tenant->subscription_plan_id == $plan->id)>
                                            {{ $plan->name }}{{ $plan->isFree() ? ' - Free' : ' - PHP '.number_format((float) $plan->price, 2).' / '.$cycleLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="tenant-primary-action inline-flex w-full items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm">
                                Update Plan
                            </button>
                        </form>

                        <a
                            href="{{ route('admin.monitoring.show', $tenant) }}"
                            class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200 dark:hover:bg-slate-800"
                        >
                            Resource Monitoring
                        </a>

                        <a
                            href="{{ route('admin.invoices.index', ['tenant' => $tenant->id]) }}"
                            class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200 dark:hover:bg-slate-800"
                        >
                            View Invoices
                        </a>

                        @if (! $tenant->is_paid)
                            <form method="POST" action="{{ route('admin.tenants.mark-paid', $tenant) }}">
                                @csrf
                                @method('PATCH')

                                <button
                                    type="submit"
                                    onclick="return confirm('Mark this tenant as paid? This will remove trial restrictions.')"
                                    class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                                >
                                    Mark as Paid
                                </button>
                            </form>
                        @elseif ($subscriptionPlan && ! $subscriptionPlan->isFree())
                            <form method="POST" action="{{ route('admin.tenants.mark-unpaid', $tenant) }}">
                                @csrf
                                @method('PATCH')

                                <button
                                    type="submit"
                                    onclick="return confirm('Revoke paid status? Trial restrictions will apply again.')"
                                    class="inline-flex w-full items-center justify-center rounded-xl bg-slate-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 dark:bg-slate-600 dark:hover:bg-slate-500"
                                >
                                    Revoke Paid Status
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        @if ($additionalData->isNotEmpty())
            <section class="tenant-panel shop-card p-6">
                <div class="relative z-10">
                    <div class="mb-6 border-b border-slate-200 pb-4 dark:border-slate-800">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Additional Metadata</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Extra tenant fields currently stored in the central record.</p>
                    </div>

                    <dl class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($additionalData as $key => $value)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-950/50">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                                    {{ str_replace('_', ' ', $key) }}
                                </dt>
                                <dd class="mt-2 break-words text-sm font-semibold text-slate-900 dark:text-slate-100">
                                    {{ is_array($value) ? json_encode($value) : $value }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </section>
        @endif

        <section class="tenant-panel shop-card p-6">
            <div class="relative z-10">
                <form method="POST" action="{{ route('admin.tenants.update-features', $tenant) }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-6 flex flex-col gap-4 border-b border-slate-200 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Feature Configuration</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Toggle the features available for this shop.</p>
                        </div>

                        <button type="submit" class="tenant-primary-action inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm">
                            Save Changes
                        </button>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($featureCatalog as $featureKey => $featureDef)
                            @php
                                $requires = collect($featureDef['requires'] ?? [])
                                    ->map(fn (string $feature): string => config("themes.features.{$feature}.label", $feature))
                                    ->implode(', ');
                                $featureDescription = preg_replace(
                                    '/\s*[^\x20-\x7E]+\s*/',
                                    ' - ',
                                    $featureDef['description'] ?? ''
                                ) ?? ($featureDef['description'] ?? '');
                                $featureDescription = preg_replace('/\s+/', ' ', trim($featureDescription)) ?? $featureDescription;
                            @endphp

                            <label class="flex h-full items-start justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 transition hover:border-slate-300 dark:border-slate-800 dark:bg-slate-950/50 dark:hover:border-slate-700">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $featureDef['label'] }}</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $featureDescription }}</p>
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                            {{ ucfirst($featureDef['category'] ?? 'general') }}
                                        </span>

                                        @if ($requires !== '')
                                            <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-amber-600 dark:text-amber-300">
                                                Requires: {{ $requires }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="shrink-0">
                                    <input
                                        type="checkbox"
                                        name="features[]"
                                        value="{{ $featureKey }}"
                                        class="feature-toggle sr-only"
                                        @checked(in_array($featureKey, $enabledFeatures, true))
                                    >
                                    <span class="feature-switch"></span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </form>
            </div>
        </section>

        <section class="rounded-[28px] border border-red-200 bg-white p-6 shadow-sm dark:border-red-500/30 dark:bg-slate-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-red-600 dark:text-red-300">Danger Zone</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Deleting this shop permanently removes its customers, orders, users, and tenant data.
                    </p>
                </div>

                <button
                    type="button"
                    x-data=""
                    x-on:click="$dispatch('open-modal', 'confirm-shop-deletion')"
                    class="inline-flex items-center justify-center rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700"
                >
                    Delete Shop
                </button>
            </div>
        </section>

        <x-modal name="confirm-shop-deletion" focusable>
            <form method="POST" action="{{ route('admin.tenants.destroy', $tenant) }}" class="p-6">
                @csrf
                @method('DELETE')

                <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">
                    Are you sure you want to delete this shop?
                </h2>

                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    Once deleted, all data for <strong>{{ $shopName }}</strong> will be permanently removed.
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        x-on:click="$dispatch('close')"
                        class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="inline-flex items-center rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700"
                    >
                        Delete Shop
                    </button>
                </div>
            </form>
        </x-modal>
    </div>
</x-admin-layout>
