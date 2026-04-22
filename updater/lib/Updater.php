<?php

declare(strict_types=1);

/**
 * Orchestrates a single update: stop Apache, swap files, install deps, run
 * migrations, restart Apache. On any failure, restores the backup and starts
 * Apache so the shop comes back online on the previous version.
 *
 * Designed for non-technical customers: every failure path ends with a
 * working Apache on the old code. Recovery never requires a human with CLI
 * access unless Apache itself fails to restart.
 */
final class Updater
{
    private array $config;
    private StatusWriter $status;
    private ApacheControl $apache;
    private FileSync $sync;

    private string $appRoot;
    private string $pendingDir;
    private string $backupDir;
    private array $manifest;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->validateConfig();

        $this->appRoot = $this->rtrimSlash($config['app_root']);
        $this->pendingDir = $this->rtrimSlash($config['pending_dir']);
        $this->backupDir = $this->rtrimSlash($config['previous_backup_dir']);
        $this->manifest = $config['manifest'];

        $this->status = new StatusWriter($config['status_file'], $config['log_file']);
        $this->apache = new ApacheControl(
            $config['apache_service'] ?? null,
            $config['apache_start_bat'] ?? null,
            $this->status,
        );
        $this->sync = new FileSync($this->status);
    }

    public function run(): void
    {
        $this->status->update('start', "Starting update to {$this->config['target_version']}", 'running', [
            'target_version' => $this->config['target_version'],
        ]);

        $this->preflightChecks();

        $this->apache->stop();

        try {
            $this->sync->backup($this->appRoot, $this->backupDir, $this->manifest);
            $this->status->update('backup', "Backup created at {$this->backupDir}");

            $this->sync->sync($this->pendingDir, $this->appRoot, $this->manifest, 'deployment');
            $this->status->update('swap', 'Code files swapped into place');

            $this->runComposerInstall();
            $this->runNpmBuild();
            $this->runMigrations();
            $this->runCacheBuild();
        } catch (Throwable $e) {
            $this->rollback($e);
            throw $e;
        }

        $this->apache->start();

        $this->status->finalize('success', "Updated to {$this->config['target_version']}", [
            'target_version' => $this->config['target_version'],
            'backup_dir' => $this->backupDir,
        ]);
    }

    public function handleFailure(Throwable $e): void
    {
        $this->status->finalize('failed', 'Update failed: ' . $e->getMessage(), [
            'error' => $e->getMessage(),
            'backup_dir' => $this->backupDir,
        ]);
    }

    private function preflightChecks(): void
    {
        $this->status->update('preflight', 'Running pre-flight checks');

        if (! is_dir($this->appRoot) || ! is_writable($this->appRoot)) {
            throw new RuntimeException("App root [{$this->appRoot}] is missing or not writable.");
        }

        if (! is_dir($this->pendingDir)) {
            throw new RuntimeException("Pending update dir [{$this->pendingDir}] not found.");
        }

        $minBytes = ((int) ($this->config['min_free_space_mb'] ?? 500)) * 1024 * 1024;
        $free = @disk_free_space($this->appRoot);
        if ($free === false || $free < $minBytes) {
            throw new RuntimeException('Insufficient free disk space for update.');
        }

        foreach ($this->manifest['directories'] as $dir) {
            $sourceDir = $this->appRoot . DIRECTORY_SEPARATOR . $dir;
            if (! is_dir($sourceDir)) {
                continue;
            }
            // Empty-existence check; detailed write-permission verified by the
            // backup step that actually writes.
        }
    }

    private function rollback(Throwable $cause): void
    {
        $this->status->update('rollback', 'Update failed, restoring previous version: ' . $cause->getMessage());

        try {
            $this->sync->sync($this->backupDir, $this->appRoot, $this->manifest, 'rollback');
            $this->status->update('rollback', 'Previous version restored');
        } catch (Throwable $rollbackError) {
            $this->status->update('rollback', 'CRITICAL: rollback failed: ' . $rollbackError->getMessage(), 'failed');
        }

        try {
            $this->apache->start();
        } catch (Throwable $apacheError) {
            $this->status->update('apache_start', 'CRITICAL: Apache failed to restart after rollback: ' . $apacheError->getMessage(), 'failed');
        }
    }

    private function runComposerInstall(): void
    {
        if (empty($this->config['run_composer'])) {
            return;
        }

        $composer = $this->config['composer_binary'] ?? 'composer';
        $this->status->update('composer', 'Installing PHP dependencies');

        $this->runShell(
            [$composer, 'install', '--no-dev', '--prefer-dist', '--no-interaction', '--no-progress', '--optimize-autoloader'],
            'composer install'
        );
    }

    private function runNpmBuild(): void
    {
        if (empty($this->config['run_npm_build'])) {
            return;
        }

        $npm = $this->config['npm_binary'] ?? 'npm';
        $installCmd = is_file($this->appRoot . DIRECTORY_SEPARATOR . 'package-lock.json')
            ? [$npm, 'ci', '--no-audit', '--no-fund']
            : [$npm, 'install', '--no-audit', '--no-fund'];

        $this->status->update('npm', 'Installing frontend dependencies');
        $this->runShell($installCmd, 'npm install');

        $this->status->update('npm', 'Building frontend assets');
        $this->runShell([$npm, 'run', 'build'], 'npm run build');

        $manifest = $this->appRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'manifest.json';
        if (! is_file($manifest)) {
            throw new RuntimeException('Vite build manifest missing after npm run build.');
        }
    }

    private function runMigrations(): void
    {
        $php = $this->config['php_binary'] ?? 'php';

        if (! empty($this->config['run_migrations'])) {
            $this->status->update('migrate', 'Running central migrations');
            $this->runShell([$php, 'artisan', 'migrate', '--force'], 'php artisan migrate');
        }

        if (! empty($this->config['run_tenant_migrations'])) {
            $this->status->update('migrate', 'Running tenant migrations');
            $this->runShell([$php, 'artisan', 'tenants:migrate', '--force'], 'php artisan tenants:migrate');
        }
    }

    private function runCacheBuild(): void
    {
        $php = $this->config['php_binary'] ?? 'php';

        foreach (['cache:clear', 'config:clear', 'route:clear', 'view:clear'] as $sig) {
            $this->status->update('cache', "php artisan {$sig}");
            $this->runShell([$php, 'artisan', $sig], "php artisan {$sig}");
        }

        foreach (['config:cache', 'route:cache', 'view:cache'] as $sig) {
            $this->status->update('cache', "php artisan {$sig}");
            $this->runShell([$php, 'artisan', $sig], "php artisan {$sig}");
        }
    }

    /**
     * @param array<int, string> $args
     */
    private function runShell(array $args, string $label): void
    {
        $command = implode(' ', array_map(static fn (string $a): string => escapeshellarg($a), $args));
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $this->appRoot);
        if (! is_resource($process)) {
            throw new RuntimeException("Failed to launch [{$label}].");
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exit = proc_close($process);

        if ($exit !== 0) {
            $tail = trim($stdout . "\n" . $stderr);
            if (strlen($tail) > 2000) {
                $tail = substr($tail, -2000);
            }
            throw new RuntimeException("[{$label}] failed (exit {$exit}): {$tail}");
        }
    }

    private function validateConfig(): void
    {
        $required = ['app_root', 'pending_dir', 'previous_backup_dir', 'manifest', 'status_file', 'log_file', 'target_version'];
        foreach ($required as $key) {
            if (! array_key_exists($key, $this->config)) {
                throw new RuntimeException("Updater config missing required key [{$key}].");
            }
        }

        if (! is_array($this->config['manifest']) || ! isset($this->config['manifest']['directories'], $this->config['manifest']['files'])) {
            throw new RuntimeException('Updater config manifest must have directories and files keys.');
        }

        if (! preg_match('/^v?\d+\.\d+\.\d+(?:-[\w.]+)?$/', (string) $this->config['target_version'])) {
            throw new RuntimeException('Updater config target_version is not a valid semver tag.');
        }
    }

    private function rtrimSlash(string $path): string
    {
        return rtrim($path, "/\\");
    }
}
