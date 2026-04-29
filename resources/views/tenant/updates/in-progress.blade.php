<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Update Center</title>
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
            --track: rgba(148, 163, 184, 0.22);
        }

        * {
            box-sizing: border-box;
        }

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

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 24px;
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
            max-width: 720px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
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

        .button.primary {
            background: linear-gradient(135deg, #5664ff 0%, #7985ff 100%);
            border-color: rgba(121, 133, 255, 0.55);
        }

        .grid {
            display: grid;
            gap: 24px;
        }

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

        .status-head {
            display: flex;
            align-items: flex-start;
            gap: 18px;
        }

        .pulse {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            background: var(--accent-soft);
            border: 1px solid rgba(109, 120, 255, 0.28);
            display: grid;
            place-items: center;
            flex: 0 0 auto;
        }

        .pulse::after {
            content: "";
            width: 14px;
            height: 14px;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 14px rgba(109, 120, 255, 0.6);
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

        .log-body {
            padding: 20px 24px 24px;
        }

        .log-list {
            list-style: none;
            margin: 0;
            padding: 0;
            font-family: Consolas, "Courier New", monospace;
            font-size: 13px;
            line-height: 1.55;
            color: #c3d0e7;
        }

        .log-list li + li {
            margin-top: 10px;
        }

        .empty-log {
            color: #7f8ba2;
            font-style: italic;
        }

        @media (max-width: 720px) {
            .shell {
                width: min(100% - 24px, 1080px);
                padding-top: 22px;
            }

            .topbar {
                flex-direction: column;
            }

            .actions,
            .button {
                width: 100%;
            }

            .panel-body,
            .log-header,
            .log-body {
                padding-left: 18px;
                padding-right: 18px;
            }

            .status-head {
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
                    Update Center
                </div>
                <h1>Applying {{ $release->version_tag }}</h1>
                <p class="subtitle">
                    {{ $shopName }} is updating right now. This page stays outside the tenant layout so it remains stable while the update is running.
                </p>
            </div>

            <div class="actions">
                <a class="button primary" href="{{ route('tenant.updates.status', $release->id) }}">View Live Status</a>
                <a class="button" href="{{ route('tenant.updates.index') }}">Refresh</a>
            </div>
        </div>

        <div class="grid">
            <section class="panel">
                <div class="panel-body">
                    <div class="status-head">
                        <div class="pulse"></div>
                        <div>
                            <h2 class="status-title" id="stage-title">Update in progress...</h2>
                            <p class="status-message" id="stage-message">Waiting for the updater to report the current stage.</p>
                        </div>
                    </div>

                    <div class="progress-track">
                        <div class="progress-bar" id="progress-bar"></div>
                    </div>

                    <div class="meta" id="network-hint">This page checks progress automatically. Open the live status page at any time for detailed logs.</div>
                </div>
            </section>

            <section class="panel">
                <div class="log-header">Recent Progress</div>
                <div class="log-body">
                    <ol class="log-list" id="step-log">
                        <li class="empty-log">Waiting for first status...</li>
                    </ol>
                </div>
            </section>
        </div>
    </div>

    <script>
        (() => {
            const POLL_URL = @json(route('tenant.updates.poll', $release->id));
            const STATUS_URL = @json(route('tenant.updates.status', $release->id));

            const stageTitle = document.getElementById('stage-title');
            const stageMessage = document.getElementById('stage-message');
            const progressBar = document.getElementById('progress-bar');
            const networkHint = document.getElementById('network-hint');
            const stepLog = document.getElementById('step-log');

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

            let renderedHistory = new Set();
            let failedFetches = 0;

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

                stageTitle.textContent = data.message || 'Update in progress...';
                stageMessage.textContent = data.error || 'The updater is still running.';
                progressBar.style.width = `${percent}%`;

                renderHistory(data.history);

                if (state === 'success' || state === 'failed') {
                    window.location.href = STATUS_URL;
                    return;
                }

                setTimeout(poll, 3000);
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
                        networkHint.textContent = 'This page checks progress automatically. Open the live status page at any time for detailed logs.';
                        renderStatus(data);
                    })
                    .catch(() => {
                        failedFetches += 1;
                        networkHint.textContent = failedFetches < 3
                            ? 'The updater is restarting services. Retrying automatically...'
                            : `Still waiting for the updater to respond (attempt ${failedFetches}).`;
                        setTimeout(poll, failedFetches < 4 ? 3000 : 5000);
                    });
            }

            poll();
        })();
    </script>
</body>
</html>
