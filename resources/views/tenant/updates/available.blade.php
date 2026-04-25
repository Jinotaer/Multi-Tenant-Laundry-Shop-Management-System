<x-tenant-layout>
    <div class="space-y-5">
        <x-tenant-header title="Available Updates" description="Review newer releases you can install for this store.">
            <x-slot name="actions">
                <a
                    href="{{ route('tenant.updates.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200 dark:hover:bg-slate-800"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Back to Update Center
                </a>
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

        @php $tenantCanDeployCode = config('updates.auto_deploy_code', false) && config('updates.allow_tenant_code_deploy', false); @endphp

        @if ($availableUpdates->isEmpty())
            <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white/92 px-6 py-12 text-center shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <svg class="mx-auto h-10 w-10 text-emerald-400 dark:text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-200">You're up to date</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">No newer releases are available right now.</p>
            </div>
        @else
            @include('tenant.updates.partials.available-updates')
        @endif
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
