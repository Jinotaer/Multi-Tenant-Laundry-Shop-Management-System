<x-tenant-layout>
    <div class="tenant-page-stack space-y-5">
        <x-tenant-header title="Updating to {{ $release->version_tag }}" description="Do not close this tab while the update is in progress.">
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

        @if (session('error'))
            <div class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        {{-- Finalize form — submitted automatically by JS when updater reports success --}}
        <form id="finalize-form" action="{{ route('tenant.updates.finalize', $release->id) }}" method="POST" class="hidden">
            @csrf
        </form>

        {{-- Progress card --}}
        <div class="tenant-panel p-6">

            {{-- Running state --}}
            <div id="state-running">
                <div class="flex items-start gap-4">
                    <div class="mt-0.5 shrink-0">
                        <svg id="spinner-icon" class="h-9 w-9 animate-spin text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-base font-bold text-slate-900 dark:text-slate-100" id="stage-title">
                            Update in progress…
                        </p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400" id="stage-message">
                            The updater is starting. Apache may briefly stop; this page will resume polling automatically.
                        </p>
                    </div>
                </div>

                <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                    <div
                        class="h-full animate-pulse rounded-full transition-all duration-500"
                        id="progress-bar"
                        style="width: 20%; background: var(--tenant-theme-accent);"
                    ></div>
                </div>

                <p class="mt-3 text-xs text-slate-400 dark:text-slate-500" id="network-hint">
                    Keep this tab open. The page will update automatically when the process completes.
                </p>
            </div>

            {{-- Success state --}}
            <div id="state-success" class="hidden py-4 text-center">
                <svg class="mx-auto h-12 w-12 text-emerald-500 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-3 text-base font-bold text-slate-900 dark:text-slate-100">Update complete!</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Finalising version record…</p>
            </div>

            {{-- Failed state --}}
            <div id="state-failed" class="hidden">
                <div class="flex items-center gap-3">
                    <svg class="h-8 w-8 shrink-0 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <p class="text-base font-bold text-red-700 dark:text-red-400">Update failed. previous version restored</p>
                </div>
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">Error details:</p>
                <pre id="error-detail" class="mt-2 overflow-x-auto whitespace-pre-wrap rounded-xl border border-red-300 bg-red-50 p-4 font-mono text-xs text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300"></pre>
            </div>
        </div>

        {{-- Stall warning --}}
        <div
            id="stall-warning"
            class="hidden overflow-hidden rounded-2xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-700/60 dark:bg-amber-900/20"
        >
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div class="min-w-0 flex-1 text-sm">
                    <p class="font-semibold text-amber-900 dark:text-amber-200">The updater seems slow to respond</p>
                    <p id="stall-detail" class="mt-0.5 text-amber-800 dark:text-amber-300">No progress update for a while. Check the log below for the actual error.</p>
                </div>
            </div>
        </div>

        {{-- Step log --}}
        <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white/92 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Progress Log</h3>
            </div>
            <div class="p-5">
                <ol id="step-log" class="space-y-1.5 font-mono text-xs text-slate-500 dark:text-slate-400">
                    <li class="italic text-slate-400 dark:text-slate-500">Waiting for first status…</li>
                </ol>
            </div>
        </div>

        {{-- Updater log tail (shown when stalled or failed) --}}
        <details
            id="log-tail-container"
            class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
        >
            <summary class="cursor-pointer select-none px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800/60">
                Updater CLI log (last lines)
            </summary>
            <pre id="log-tail" class="max-h-80 overflow-auto border-t border-slate-200 bg-slate-900 p-4 font-mono text-[11px] leading-relaxed text-slate-100 dark:border-slate-700"></pre>
        </details>
    </div>

    <script>
    (() => {
        const POLL_URL = @json(route('tenant.updates.poll', $release->id));
        const stageTitle   = document.getElementById('stage-title');
        const stageMessage = document.getElementById('stage-message');
        const progressBar  = document.getElementById('progress-bar');
        const networkHint  = document.getElementById('network-hint');
        const stepLog      = document.getElementById('step-log');
        const stateRunning = document.getElementById('state-running');
        const stateSuccess = document.getElementById('state-success');
        const stateFailed  = document.getElementById('state-failed');
        const errorDetail  = document.getElementById('error-detail');
        const finalizeForm = document.getElementById('finalize-form');

        const STAGE_PROGRESS = {
            queued: 3, launching: 5, booting: 7,
            start: 10, preflight: 14, backup: 22,
            swap: 35, composer: 50, npm: 70, migrate: 85, cache: 92,
            rollback: 40, finalize: 100,
        };

        const stallWarning   = document.getElementById('stall-warning');
        const stallDetail    = document.getElementById('stall-detail');
        const logTailBox     = document.getElementById('log-tail-container');
        const logTail        = document.getElementById('log-tail');

        let failedFetches  = 0;
        let loggedStages   = new Set();
        let finalizeTriggered = false;

        function poll() {
            fetch(POLL_URL, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                cache: 'no-store',
            })
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(data => {
                failedFetches = 0;
                networkHint.textContent = 'Keep this tab open. The page will update automatically when the process completes.';
                renderStatus(data);
                if (data.state !== 'success' && data.state !== 'failed') setTimeout(poll, 3000);
            })
            .catch(() => {
                failedFetches++;
                const delay = failedFetches < 5 ? 3000 : 5000;
                networkHint.textContent = failedFetches < 3
                    ? 'Apache may be restarting, retrying…'
                    : `Apache is down. Retrying (attempt ${failedFetches})… Do not close this tab.`;
                setTimeout(poll, delay);
            });
        }

        function renderStatus(data) {
            const stage   = data.stage  || 'queued';
            const state   = data.state  || 'running';
            const message = data.message || stage;
            const pct     = STAGE_PROGRESS[stage] || 20;

            progressBar.style.width = pct + '%';

            if (Array.isArray(data.history)) {
                data.history.forEach(entry => {
                    const key = entry.at + entry.stage;
                    if (loggedStages.has(key)) return;
                    loggedStages.add(key);
                    const li = document.createElement('li');
                    const ts = new Date(entry.at).toLocaleTimeString();
                    li.textContent = `[${ts}] ${entry.stage}: ${entry.message}`;
                    if (stepLog.querySelector('.italic')) stepLog.innerHTML = '';
                    stepLog.appendChild(li);
                });
            }

            // Stall / log-tail rendering — always update, regardless of state.
            if (data.log_tail) {
                logTail.textContent = data.log_tail;
                logTailBox.classList.remove('hidden');
            }

            if (data.stalled) {
                stallWarning.classList.remove('hidden');
                if (typeof data.stalled_for === 'number') {
                    stallDetail.textContent = `No progress update for ${data.stalled_for}s. The CLI may be stuck during boot or composer install. see the log below.`;
                }
                if (logTailBox) logTailBox.open = true;
            } else {
                stallWarning.classList.add('hidden');
            }

            if (state === 'success') {
                stateRunning.classList.add('hidden');
                stateSuccess.classList.remove('hidden');
                stateFailed.classList.add('hidden');
                if (!finalizeTriggered) {
                    finalizeTriggered = true;
                    setTimeout(() => finalizeForm.submit(), 1200);
                }
                return;
            }

            if (state === 'failed') {
                stateRunning.classList.add('hidden');
                stateSuccess.classList.add('hidden');
                stateFailed.classList.remove('hidden');
                errorDetail.textContent = message + (data.error ? '\n\n' + data.error : '');
                if (logTailBox) logTailBox.open = true;
                return;
            }

            stageTitle.textContent = stageLabelFor(stage);
            stageMessage.textContent = message;
        }

        function stageLabelFor(stage) {
            return {
                queued:    'Queued, waiting for updater…',
                launching: 'Launching updater process…',
                booting:   'Booting updater CLI…',
                start:     'Starting update…',
                preflight: 'Running preflight checks…',
                backup:    'Creating code backup…',
                swap:      'Swapping files…',
                composer:  'Installing PHP dependencies (composer)…',
                npm:       'Building frontend assets (npm)…',
                migrate:   'Running database migrations…',
                cache:     'Rebuilding cache…',
                rollback:  'Rolling back (restoring previous version)…',
                finalize:  'Finishing up…',
            }[stage] || 'Update in progress…';
        }

        setTimeout(poll, 2000);
    })();
    </script>
</x-tenant-layout>
