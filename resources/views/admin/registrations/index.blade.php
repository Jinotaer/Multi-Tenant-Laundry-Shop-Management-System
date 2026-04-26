<x-admin-layout>
    <x-slot name="header">
        <x-admin-header title="Shop Registrations" description="Review and manage shop registration requests.">
            <x-slot name="actions">
                <a
                    href="{{ route('shop.register') }}"
                    class="tenant-primary-action inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-semibold shadow-sm"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    New Registration
                </a>
            </x-slot>
        </x-admin-header>
    </x-slot>

    <style>
        .registration-page .registration-shell {
            background: rgb(255 255 255 / 0.92);
        }

        .dark .registration-page .registration-shell {
            background: rgb(15 23 42 / 0.92);
        }

        .registration-page .registration-table thead th {
            letter-spacing: 0.18em;
        }

        .registration-page .registration-action-stack {
            display: inline-flex;
            flex-wrap: nowrap;
            gap: 0.5rem;
            align-items: center;
            white-space: nowrap;
        }

        .registration-page .registration-action-button {
            display: inline-flex;
            justify-content: center;
            white-space: nowrap;
        }

        .registration-page .registration-danger-action {
            background-color: rgb(225 29 72) !important;
            color: rgb(255 255 255) !important;
        }

        .registration-page .registration-danger-action:hover {
            background-color: rgb(190 24 93) !important;
        }
    </style>

    <div class="registration-page admin-page-stack space-y-6">
        @if (session('error'))
            <div class="tenant-alert border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="tenant-alert border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
                <p class="font-semibold">Please review the form errors below.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Total Applications</p>
                <p class="mt-1.5 text-xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    {{ number_format($registrationSummary['total'] ?? 0) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Pending Queue</p>
                <p class="mt-1.5 text-xl font-black tracking-tight text-amber-600 dark:text-amber-300">
                    {{ number_format($registrationSummary['pending'] ?? 0) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Approved</p>
                <p class="mt-1.5 text-xl font-black tracking-tight text-emerald-600 dark:text-emerald-300">
                    {{ number_format($registrationSummary['approved'] ?? 0) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Rejected</p>
                <p class="mt-1.5 text-xl font-black tracking-tight text-rose-600 dark:text-rose-300">
                    {{ number_format($registrationSummary['rejected'] ?? 0) }}
                </p>
            </div>
        </section>

        <section class="registration-shell rounded-[28px] border border-slate-200 p-5 shadow-sm dark:border-slate-800 sm:p-8">
            @if ($registrations->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-12 text-center dark:border-slate-700">
                    <p class="text-base font-semibold text-slate-900 dark:text-slate-100">No registration applications yet.</p>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        New shop applications will appear here once store owners submit the registration form.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="registration-table min-w-full border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-left dark:bg-slate-950/60">
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">Shop Name</th>
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">Subdomain</th>
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">Owner</th>
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">Status</th>
                                <th class="px-6 py-4 text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($registrations as $registration)
                                @php
                                    $tenantExists = in_array($registration->subdomain, $approvedTenantIds ?? [], true);
                                    $viewShopUrl = $tenantExists ? route('admin.tenants.show', $registration->subdomain) : null;
                                    $statusClasses = match ($registration->status) {
                                        'approved' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200',
                                        'rejected' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-200',
                                        default => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-200',
                                    };
                                @endphp

                                <tr class="border-t border-slate-200 transition hover:bg-slate-50/70 dark:border-slate-800 dark:hover:bg-slate-950/40">
                                    <td class="px-6 py-5 align-top">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $registration->shop_name }}</p>
                                    </td>

                                    <td class="px-6 py-5 align-top">
                                        <p class="text-sm text-slate-700 dark:text-slate-200">{{ $registration->subdomain }}.localhost:8000</p>
                                    </td>

                                    <td class="px-6 py-5 align-top">
                                        @if ($registration->owner_name)
                                            <p class="text-sm text-slate-700 dark:text-slate-200">{{ $registration->owner_name }}</p>
                                        @endif
                                        <p class="{{ $registration->owner_name ? 'mt-1' : '' }} text-sm text-slate-500 dark:text-slate-400">{{ $registration->owner_email }}</p>
                                    </td>

                                    <td class="px-6 py-5 align-top">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClasses }}">
                                            {{ ucfirst($registration->status) }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-5 align-top">
                                        @if ($registration->isPending())
                                            <div class="registration-action-stack">
                                                <form method="POST" action="{{ route('admin.registrations.approve', $registration) }}" class="shrink-0">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        onclick="return confirm('Approve this shop registration? A new tenant database and domain will be created.')"
                                                        class="registration-action-button tenant-primary-action inline-flex items-center rounded-xl px-3.5 py-2 text-xs font-semibold shadow-sm"
                                                    >
                                                        Approve
                                                    </button>
                                                </form>

                                                <button
                                                    type="button"
                                                    x-data=""
                                                    x-on:click="$dispatch('open-modal', 'reject-registration-{{ $registration->id }}')"
                                                    class="registration-action-button registration-danger-action inline-flex items-center rounded-xl px-3.5 py-2 text-xs font-semibold shadow-sm transition"
                                                >
                                                    Reject
                                                </button>
                                            </div>

                                            <x-modal name="reject-registration-{{ $registration->id }}" focusable>
                                                <form method="POST" action="{{ route('admin.registrations.reject', $registration) }}" class="p-6">
                                                    @csrf

                                                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">
                                                        Reject Registration
                                                    </h2>

                                                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                                        Rejecting <strong>{{ $registration->shop_name }}</strong> by {{ $registration->owner_name }}.
                                                    </p>

                                                    <div class="mt-6">
                                                        <label for="rejection_reason_{{ $registration->id }}" class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                                            Reason (optional)
                                                        </label>
                                                        <textarea
                                                            id="rejection_reason_{{ $registration->id }}"
                                                            name="rejection_reason"
                                                            rows="4"
                                                            class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                                                            placeholder="Provide a reason for rejection..."
                                                        ></textarea>
                                                    </div>

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
                                                            class="registration-danger-action inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold transition"
                                                        >
                                                            Reject Registration
                                                        </button>
                                                    </div>
                                                </form>
                                            </x-modal>
                                        @elseif ($registration->isApproved())
                                            <div class="space-y-1">
                                                @if ($viewShopUrl)
                                                    <a href="{{ $viewShopUrl }}" class="text-xs font-semibold hover:underline" style="color: var(--tenant-theme-accent);">
                                                        Open Shop
                                                    </a>
                                                @endif
                                            </div>
                                        @else
                                            <div class="space-y-1">
                                                <p class="text-xs font-semibold text-rose-600 dark:text-rose-300">
                                                    Rejected
                                                </p>
                                                @if ($registration->rejection_reason)
                                                    <p class="max-w-xs text-[11px] leading-5 text-slate-500 dark:text-slate-400" title="{{ $registration->rejection_reason }}">
                                                        {{ \Illuminate\Support\Str::limit($registration->rejection_reason, 60) }}
                                                    </p>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $registrations->links() }}
                </div>
            @endif
        </section>
    </div>
</x-admin-layout>
