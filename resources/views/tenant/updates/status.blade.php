<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-slate-100 leading-tight">
            Updating to {{ $release->version_tag }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Server-side status (shown on first load / after Apache restart) --}}
            @if(session('error'))
                <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700 border border-red-200 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Finalize form — submitted automatically by JS when updater reports success --}}
            <form id="finalize-form"
                  action="{{ route('tenant.updates.finalize', $release->id) }}"
                  method="POST"
                  class="hidden">
                @csrf
            </form>

            {{-- Progress card --}}
            <div class="p-6 bg-white dark:bg-slate-900 shadow sm:rounded-lg">

                {{-- Waiting / in-progress state --}}
                <div id="state-running">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex-shrink-0">
                            <svg id="spinner-icon" class="h-10 w-10 animate-spin text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-slate-100" id="stage-title">
                                Update in progress&hellip;
                            </p>
                            <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5" id="stage-message">
                                The updater is starting. Apache may briefly stop — this page will resume polling automatically.
                            </p>
                        </div>
                    </div>

                    <div class="h-2 overflow-hidden rounded-full bg-blue-100 dark:bg-blue-900/40">
                        <div class="h-full animate-pulse rounded-full bg-blue-500 dark:bg-blue-400" id="progress-bar" style="width: 20%"></div>
                    </div>

                    <p class="mt-3 text-xs text-gray-400 dark:text-slate-500" id="network-hint">
                        Keep this tab open. The page will update automatically when the process completes.
                    </p>
                </div>

                {{-- Success state (hidden until JS detects it) --}}
                <div id="state-success" class="hidden text-center py-4">
                    <svg class="mx-auto h-12 w-12 text-green-500 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-3 font-semibold text-gray-900 dark:text-slate-100">
                        Update complete!
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                        Finalising version record&hellip;
                    </p>
                </div>

                {{-- Failed state (hidden until JS detects it) --}}
                <div id="state-failed" class="hidden">
                    <div class="flex items-center gap-3 mb-4">
                        <svg class="h-8 w-8 text-red-500 dark:text-red-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        <p class="font-semibold text-red-700 dark:text-red-400">Update failed — previous version restored</p>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-slate-400 mb-2">Error details:</p>
                    <pre id="error-detail" class="text-xs bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-3 overflow-x-auto text-red-700 dark:text-red-300 whitespace-pre-wrap"></pre>
                    <div class="mt-4">
                        <a href="{{ route('tenant.updates.index') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-md font-medium text-sm text-gray-700 dark:text-slate-300 transition">
                            Back to Update Center
                        </a>
                    </div>
                </div>
            </div>

            {{-- Step log (populated by JS from status.json history) --}}
            <div class="p-4 sm:p-6 bg-white dark:bg-slate-900 shadow sm:rounded-lg">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-300 mb-3">Progress log</h3>
                <ol id="step-log" class="space-y-1 text-xs text-gray-500 dark:text-slate-400 font-mono">
                    <li class="text-gray-400 dark:text-slate-500 italic">Waiting for first status…</li>
                </ol>
            </div>

        </div>
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

        // Approximate progress per stage so the bar moves even without timestamps.
        const STAGE_PROGRESS = {
            queued: 5, start: 8, preflight: 12, backup: 20,
            swap: 35, composer: 50, npm: 70, migrate: 85, cache: 92,
            rollback: 40, finalize: 100,
        };

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

                if (data.state !== 'success' && data.state !== 'failed') {
                    setTimeout(poll, 3000);
                }
            })
            .catch(() => {
                failedFetches++;
                const delay = failedFetches < 5 ? 3000 : 5000;
                networkHint.textContent = failedFetches < 3
                    ? 'Apache may be restarting — retrying…'
                    : `Apache is down. Retrying (attempt ${failedFetches})… Do not close this tab.`;
                setTimeout(poll, delay);
            });
        }

        function renderStatus(data) {
            const stage   = data.stage  || 'queued';
            const state   = data.state  || 'running';
            const message = data.message || stage;
            const pct     = STAGE_PROGRESS[stage] || 20;

            // Update progress bar
            progressBar.style.width = pct + '%';

            // Append new history entries to the log
            if (Array.isArray(data.history)) {
                data.history.forEach(entry => {
                    const key = entry.at + entry.stage;
                    if (loggedStages.has(key)) return;
                    loggedStages.add(key);

                    const li = document.createElement('li');
                    const ts = new Date(entry.at).toLocaleTimeString();
                    li.textContent = `[${ts}] ${entry.stage}: ${entry.message}`;
                    if (stepLog.firstChild?.tagName === undefined || stepLog.querySelector('.italic')) {
                        stepLog.innerHTML = '';
                    }
                    stepLog.appendChild(li);
                });
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
                return;
            }

            // Still running
            stageTitle.textContent = stageLabelFor(stage);
            stageMessage.textContent = message;
        }

        function stageLabelFor(stage) {
            return {
                queued:    'Queued — waiting for updater…',
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

        // Begin polling after a short delay to give the updater time to start.
        setTimeout(poll, 2000);
    })();
    </script>
</x-tenant-layout>
