<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Update Status {{ $release->version_tag }}</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #081123;
            --panel: rgba(17, 24, 39, 0.92);
            --panel-border: rgba(148, 163, 184, 0.18);
            --text: #e5eefc;
            --muted: #9fb0c9;
            --accent: #6d78ff;
            --accent-soft: rgba(109, 120, 255, 0.16);
            --success: #38d39f;
            --warn: #f4b740;
            --danger: #f87171;
            --track: rgba(148, 163, 184, 0.22);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at top right, rgba(109, 120, 255, 0.16), transparent 24rem),
                linear-gradient(180deg, #0b1530 0%, var(--bg) 100%);
            color: var(--text);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .shell {
            width: min(1080px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0 40px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 24px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.72);
            border: 1px solid var(--panel-border);
            color: var(--muted);
            font-size: 13px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .eyebrow-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 12px rgba(109, 120, 255, 0.7);
        }

        h1 {
            margin: 18px 0 10px;
            font-size: clamp(36px, 5vw, 64px);
            line-height: 0.96;
            letter-spacing: -0.05em;
        }

        .subtitle {
            margin: 0;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.7;
            max-width: 760px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 56px;
            padding: 0 22px;
            border-radius: 20px;
            border: 1px solid var(--panel-border);
            background: rgba(15, 23, 42, 0.58);
            color: var(--text);
            font-size: 15px;
            font-weight: 600;
            white-space: nowrap;
        }

        .grid { display: grid; gap: 24px; }

        .panel {
            border-radius: 28px;
            border: 1px solid var(--panel-border);
            background: var(--panel);
            box-shadow: 0 28px 70px rgba(2, 6, 23, 0.35);
            overflow: hidden;
        }

        .panel-body {
            padding: 28px 30px;
        }

        .status-row {
            display: flex;
            align-items: flex-start;
            gap: 18px;
        }

        .spinner {
            width: 54px;
            height: 54px;
            border-radius: 999px;
            border: 7px solid rgba(109, 120, 255, 0.18);
            border-top-color: var(--accent);
            animation: spin 0.85s linear infinite;
            flex: 0 0 auto;
        }

        .status-title {
            margin: 0;
            font-size: clamp(24px, 3vw, 34px);
            line-height: 1.1;
            letter-spacing: -0.03em;
        }

        .status-message {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.65;
        }

        .progress-track {
            margin-top: 22px;
            height: 14px;
            border-radius: 999px;
            background: var(--track);
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            width: 18%;
            border-radius: inherit;
            background: linear-gradient(90deg, #5a67ff 0%, #7b88ff 100%);
            transition: width 0.45s ease;
        }

        .meta {
            margin-top: 16px;
            color: var(--muted);
            font-size: 14px;
        }

        .log-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--panel-border);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .log-body { padding: 20px 24px 24px; }

        .log-list {
            list-style: none;
            margin: 0;
            padding: 0;
            font-family: Consolas, "Courier New", monospace;
            font-size: 13px;
            line-height: 1.55;
            color: #c3d0e7;
        }

        .log-list li + li { margin-top: 10px; }

        .empty-log {
            color: #7f8ba2;
            font-style: italic;
        }

        .alert {
            display: none;
            margin-top: 22px;
            padding: 16px 18px;
            border-radius: 20px;
            border: 1px solid rgba(248, 113, 113, 0.3);
            background: rgba(127, 29, 29, 0.26);
            color: #fecaca;
            font-size: 14px;
            line-height: 1.65;
            white-space: pre-wrap;
        }

        .alert.show { display: block; }

        .log-tail {
            display: none;
            margin-top: 22px;
            padding: 18px;
            border-radius: 20px;
            border: 1px solid var(--panel-border);
            background: rgba(2, 6, 23, 0.72);
        }

        .log-tail.show { display: block; }

        .log-tail-title {
            margin: 0 0 12px;
            color: var(--muted);
            font-size: 12px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .log-tail pre {
            margin: 0;
            max-height: 280px;
            overflow: auto;
            font-family: Consolas, "Courier New", monospace;
            font-size: 12px;
            line-height: 1.6;
            color: #c3d0e7;
            white-space: pre-wrap;
        }

        .success .spinner {
            animation: none;
            border-color: rgba(56, 211, 159, 0.24);
            border-top-color: var(--success);
        }

        .failed .spinner {
            animation: none;
            border-color: rgba(248, 113, 113, 0.28);
            border-top-color: var(--danger);
        }

        .stalled .spinner {
            border-top-color: var(--warn);
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 720px) {
            .shell {
                width: min(100% - 24px, 1080px);
                padding-top: 22px;
            }

            .topbar {
                flex-direction: column;
            }

            .button {
                width: 100%;
            }

            .panel-body, .log-header, .log-body {
                padding-left: 18px;
                padding-right: 18px;
            }

            .status-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="topbar">
            <div>
                <div class="eyebrow">
                    <span class="eyebrow-dot"></span>
                    Update Runner
                </div>
                <h1>Updating {{ $shopName }} to {{ $release->version_tag }}</h1>
                <p class="subtitle">
                    This page stays outside the normal tenant layout so it remains readable even while application assets are changing.
                </p>
            </div>

            <a href="{{ route('tenant.updates.index') }}" class="button">Back to Update Center</a>
        </div>

        @if (session('error'))
            <div class="alert show">{{ session('error') }}</div>
        @endif

        <div class="grid">
            <section class="panel">
                <div class="panel-body">
                    <div class="status-row" id="status-shell">
                        <div class="spinner"></div>
                        <div>
                            <h2 class="status-title" id="stage-title">Update in progress...</h2>
                            <p class="status-message" id="stage-message">The updater is starting. Waiting for the first status update.</p>
                        </div>
                    </div>

                    <div class="progress-track">
                        <div class="progress-bar" id="progress-bar"></div>
                    </div>

                    <div class="meta" id="network-hint">Keep this tab open. The page will update automatically when the process completes.</div>

                    <div class="alert" id="error-detail"></div>

                    <div class="log-tail" id="log-tail-wrapper">
                        <p class="log-tail-title">Updater log tail</p>
                        <pre id="log-tail"></pre>
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="log-header">Progress Log</div>
                <div class="log-body">
                    <ol class="log-list" id="step-log">
                        <li class="empty-log">Waiting for first status...</li>
                    </ol>
                </div>
            </section>
        </div>

        <form id="finalize-form" action="{{ route('tenant.updates.finalize', $release->id) }}" method="POST" hidden>
            @csrf
        </form>
    </div>

    <script>
        (() => {
            const POLL_URL = @json(route('tenant.updates.poll', $release->id));
            const INITIAL_STATUS = @json($status);

            const stageTitle = document.getElementById('stage-title');
            const stageMessage = document.getElementById('stage-message');
            const progressBar = document.getElementById('progress-bar');
            const networkHint = document.getElementById('network-hint');
            const stepLog = document.getElementById('step-log');
            const statusShell = document.getElementById('status-shell');
            const errorDetail = document.getElementById('error-detail');
            const logTailWrapper = document.getElementById('log-tail-wrapper');
            const logTail = document.getElementById('log-tail');
            const finalizeForm = document.getElementById('finalize-form');

            const STAGE_PROGRESS = {
                queued: 4,
                launching: 8,
                booting: 12,
                start: 16,
                preflight: 24,
                backup: 36,
                swap: 48,
                composer: 60,
                npm: 76,
                migrate: 88,
                cache: 94,
                finalize: 100,
                rollback: 45
            };

            let finalizeTriggered = false;
            let failedFetches = 0;
            let renderedHistory = new Set();

            function renderHistory(history) {
                if (!Array.isArray(history) || history.length === 0) {
                    return;
                }

                if (stepLog.querySelector('.empty-log')) {
                    stepLog.innerHTML = '';
                }

                history.forEach((entry) => {
                    const key = `${entry.at || ''}:${entry.stage || ''}:${entry.message || ''}`;

                    if (renderedHistory.has(key)) {
                        return;
                    }

                    renderedHistory.add(key);

                    const item = document.createElement('li');
                    const stamp = entry.at ? new Date(entry.at).toLocaleTimeString() : '--:--:--';
                    item.textContent = `[${stamp}] ${entry.stage || 'stage'}: ${entry.message || ''}`;
                    stepLog.appendChild(item);
                });
            }

            function renderStatus(data) {
                const stage = data.stage || 'queued';
                const state = data.state || 'running';
                const percent = STAGE_PROGRESS[stage] || 18;

                progressBar.style.width = `${percent}%`;
                stageTitle.textContent = data.message || 'Update in progress...';
                stageMessage.textContent = data.error || 'The updater is still running.';

                statusShell.classList.remove('success', 'failed', 'stalled');

                if (data.stalled) {
                    statusShell.classList.add('stalled');
                }

                if (data.log_tail) {
                    logTail.textContent = data.log_tail;
                    logTailWrapper.classList.add('show');
                }

                renderHistory(data.history);

                if (state === 'success') {
                    progressBar.style.width = '100%';
                    stageTitle.textContent = 'Update complete';
                    stageMessage.textContent = 'Finalizing version record and leaving update mode...';
                    statusShell.classList.add('success');

                    if (!finalizeTriggered) {
                        finalizeTriggered = true;
                        setTimeout(() => finalizeForm.submit(), 1200);
                    }

                    return;
                }

                if (state === 'failed') {
                    stageTitle.textContent = 'Update failed';
                    stageMessage.textContent = data.message || 'The updater reported a failure.';
                    statusShell.classList.add('failed');
                    errorDetail.textContent = data.error || data.message || 'Unknown updater error.';
                    errorDetail.classList.add('show');
                    return;
                }

                errorDetail.classList.remove('show');
                errorDetail.textContent = '';
            }

            function poll() {
                fetch(POLL_URL, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    cache: 'no-store'
                })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }

                        return response.json();
                    })
                    .then((data) => {
                        failedFetches = 0;
                        networkHint.textContent = 'Keep this tab open. The page will update automatically when the process completes.';
                        renderStatus(data);

                        if (data.state !== 'success' && data.state !== 'failed') {
                            setTimeout(poll, 3000);
                        }
                    })
                    .catch(() => {
                        failedFetches += 1;
                        networkHint.textContent = failedFetches < 3
                            ? 'The updater is restarting services. Retrying automatically...'
                            : `Still waiting for the updater to respond (attempt ${failedFetches}).`;
                        setTimeout(poll, failedFetches < 4 ? 3000 : 5000);
                    });
            }

            if (INITIAL_STATUS && typeof INITIAL_STATUS === 'object') {
                renderStatus(INITIAL_STATUS);
            }

            poll();
        })();
    </script>
</body>
</html>
