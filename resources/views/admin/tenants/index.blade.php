<x-admin-layout>
    <x-slot name="header">
        <x-admin-header title="All Shops" description="Manage tenant subscriptions and payments."/>
    </x-slot>

    <style>
        .tenant-index-page .tenant-shell {
            background: rgb(255 255 255 / 0.92);
        }

        .dark .tenant-index-page .tenant-shell {
            background: rgb(15 23 42 / 0.92);
        }

        .tenant-index-page .tenant-table thead th {
            letter-spacing: 0.18em;
        }

        .tenant-index-page .tenant-action-row {
            display: inline-flex;
            flex-wrap: nowrap;
            gap: 0.5rem;
            align-items: center;
            white-space: nowrap;
        }
    </style>

    <div class="tenant-index-page admin-page-stack space-y-6">
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

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Total Shops</p>
                <p class="mt-1.5 text-xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    {{ number_format($tenantSummary['total'] ?? 0) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Enabled Access</p>
                <p class="mt-1.5 text-xl font-black tracking-tight text-emerald-600 dark:text-emerald-300">
                    {{ number_format($tenantSummary['enabled'] ?? 0) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Paid Subscriptions</p>
                <p class="mt-1.5 text-xl font-black tracking-tight" style="color: var(--tenant-theme-accent);">
                    {{ number_format($tenantSummary['paid'] ?? 0) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">On Trial</p>
                <p class="mt-1.5 text-xl font-black tracking-tight text-amber-600 dark:text-amber-300">
                    {{ number_format($tenantSummary['trial'] ?? 0) }}
                </p>
            </div>
        </section>

        <section class="tenant-shell overflow-hidden rounded-[28px] border border-slate-200 shadow-sm dark:border-slate-800">
            @if ($tenants->isEmpty())
                <div class="p-8 sm:p-10">
                    <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-12 text-center dark:border-slate-700">
                        <p class="text-base font-semibold text-slate-900 dark:text-slate-100">No shops available yet.</p>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                            Approved registrations will appear here once a tenant workspace is created.
                        </p>
                        <div class="mt-6">
                            <a
                                href="{{ route('admin.registrations.index') }}"
                                class="tenant-primary-action inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm"
                            >
                                Review Registrations
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-8">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Shop Directory</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ number_format($tenants->total()) }} approved shop{{ $tenants->total() === 1 ? '' : 's' }}
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto px-5 pb-5 pt-4 sm:px-8 sm:pb-8">
                    <table class="tenant-table min-w-[1080px] border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-left dark:bg-slate-950/60">
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">Shop Name</th>
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">Owner</th>
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">Domain</th>
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">Plan</th>
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">Status</th>
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">Created</th>
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tenants as $tenant)
                                @php
                                    $shopName = $tenant->registration->shop_name ?? str((string) $tenant->id)->replace('-', ' ')->title();
                                    $ownerName = $tenant->registration->owner_name ?? 'N/A';
                                    $ownerEmail = $tenant->registration->owner_email;
                                    $primaryDomain = $tenant->domains->first()?->domain ?? ($tenant->id.'.localhost');
                                    $extraDomainCount = max(0, $tenant->domains->count() - 1);
                                    $planClasses = match (true) {
                                        ! $tenant->subscriptionPlan => 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                                        $tenant->subscriptionPlan->isFree() => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-200',
                                        default => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-200',
                                    };
                                    $statusClasses = $tenant->isEnabled()
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200'
                                        : 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-200';
                                    $billingLabel = match (true) {
                                        $tenant->is_paid => 'Paid',
                                        $tenant->isOnTrial() => 'Trial',
                                        $tenant->needsRenewal() => 'Renewal due',
                                        default => 'Free',
                                    };
                                @endphp

                                <tr class="border-t border-slate-200 transition hover:bg-slate-50/70 dark:border-slate-800 dark:hover:bg-slate-950/40">
                                    <td class="px-6 py-5 align-top">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $shopName }}</p>
                                    </td>

                                    <td class="px-6 py-5 align-top">
                                        <p class="text-sm text-slate-900 dark:text-slate-100">{{ $ownerName }}</p>
                                        @if ($ownerEmail)
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $ownerEmail }}</p>
                                        @endif
                                    </td>

                                    <td class="px-6 py-5 align-top">
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-500/15 dark:text-blue-200">
                                            {{ $primaryDomain }}
                                        </span>
                                        @if ($extraDomainCount > 0)
                                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                                +{{ $extraDomainCount }} more domain{{ $extraDomainCount === 1 ? '' : 's' }}
                                            </p>
                                        @endif
                                    </td>

                                    <td class="px-6 py-5 align-top">
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $planClasses }}">
                                            {{ $tenant->subscriptionPlan?->name ?? 'No plan' }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-5 align-top">
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                                            {{ $tenant->isEnabled() ? 'Enabled' : 'Disabled' }}
                                        </span>
                                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $billingLabel }}</p>
                                    </td>

                                    <td class="px-6 py-5 align-top">
                                        <p class="text-sm text-slate-700 dark:text-slate-200">{{ $tenant->created_at->format('M d, Y') }}</p>
                                    </td>

                                    <td class="px-6 py-5 align-top">
                                        <div class="tenant-action-row">
                                            <a
                                                href="{{ route('admin.tenants.show', $tenant) }}"
                                                class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                            >
                                                View
                                            </a>

                                            <form method="POST" action="{{ route('admin.tenants.toggle-status', $tenant) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center rounded-xl px-3.5 py-2 text-xs font-semibold text-white transition {{ $tenant->isEnabled() ? 'bg-amber-500 hover:bg-amber-600' : 'bg-emerald-600 hover:bg-emerald-700' }}"
                                                    onclick="return confirm('{{ $tenant->isEnabled() ? 'Disable this shop? Users will not be able to access it.' : 'Enable this shop?' }}')"
                                                >
                                                    {{ $tenant->isEnabled() ? 'Disable' : 'Enable' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-8">
                    {{ $tenants->links() }}
                </div>
            @endif
        </section>
    </div>
</x-admin-layout>
