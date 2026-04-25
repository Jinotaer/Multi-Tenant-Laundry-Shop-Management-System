<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use ZipArchive;

class CodeDeploymentService
{
    private const ARTISAN_CLEAR_COMMANDS = [
        'cache:clear',
        'config:clear',
        'route:clear',
        'view:clear',
    ];

    private const ARTISAN_CACHE_COMMANDS = [
        'config:cache',
        'route:cache',
        'view:cache',
    ];

    /**
     * Strict version-tag pattern. Anything that does not match is rejected
     * before being interpolated into filesystem paths or API URLs — prevents
     * path traversal like "../../foo" being passed as a tag.
     */
    private const VERSION_TAG_PATTERN = '/^v?\d+\.\d+\.\d+(?:-[A-Za-z0-9.]+)?$/';

    private string $tempPath;

    public function __construct()
    {
        $this->tempPath = storage_path('app/deployments/temp');
        
        if (!File::exists($this->tempPath)) {
            File::makeDirectory($this->tempPath, 0755, true);
        }
    }

    /**
     * Deploy code from GitHub release.
     */
    public function deployFromGitHub(string $versionTag): array
    {
        // Prevent PHP from timing out during long download/build processes
        set_time_limit(0);

        $repo = config('services.github.repo');
        $token = config('services.github.token');
        $backupPath = null;
        $archivePath = null;
        $extractRootPath = null;
        $postDeploymentCommands = $this->getDeploymentCommandPlan();

        if (!$repo) {
            return ['success' => false, 'error' => 'GitHub repository not configured'];
        }

        try {
            $this->validateVersionTag($versionTag);

            // Download release archive
            $archivePath = $this->downloadRelease($repo, $versionTag, $token);
            
            // Extract archive
            $extractedArchive = $this->extractArchive($archivePath, $versionTag);
            $extractRootPath = $extractedArchive['root'];
            $extractPath = $extractedArchive['source'];
            $manifest = $this->buildDeploymentManifest($extractPath);
            
            // Backup current code
            if ((bool) config('updates.deployment.backup_before_deploy', true)) {
                $backupPath = $this->backupCurrentCode($manifest);
            }
            
            // Deploy new code
            $this->deployCode($extractPath, $manifest);
            
            // Run post-deployment tasks
            $postDeploymentCommands = $this->runPostDeploymentTasks();
            
            Log::info("Code deployed successfully", ['version' => $versionTag]);
            
            return [
                'success' => true,
                'version' => $versionTag,
                'backup_path' => $backupPath,
                'post_deployment_commands' => $postDeploymentCommands,
            ];
            
        } catch (\Exception $e) {
            Log::error("Code deployment failed", [
                'version' => $versionTag,
                'error' => $e->getMessage(),
                'post_deployment_commands' => $postDeploymentCommands,
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'backup_path' => $backupPath,
                'post_deployment_commands' => $postDeploymentCommands,
            ];
        } finally {
            if ($archivePath && File::exists($archivePath)) {
                File::delete($archivePath);
            }

            if ($extractRootPath && File::exists($extractRootPath)) {
                File::deleteDirectory($extractRootPath);
            }
        }
    }

    /**
     * Deploy a GitHub release with per-stage progress callbacks.
     *
     * Replaces the external apply.bat/apply.php path. Because PHP on Windows
     * does not hold open handles to .php files between requests, the live
     * application can be overwritten without stopping Apache first.
     *
     * Migrations are intentionally excluded — the caller handles them so that
     * tenant-scoped migrations can be run in the correct tenancy context.
     *
     * @param callable(string $stage, string $message): void $onProgress
     * @param null|callable(string $stage, string $message): void $onHeartbeat
     * @param null|callable(string $chunk): void $onLog
     * @return array{success: bool, backup_path: string|null, error?: string}
     */
    public function deployWithProgress(
        string $versionTag,
        callable $onProgress,
        ?callable $onHeartbeat = null,
        ?callable $onLog = null
    ): array
    {
        set_time_limit(0);
        $this->validateVersionTag($versionTag);

        $repo  = config('services.github.repo');
        $token = config('services.github.token');

        if (! $repo) {
            return ['success' => false, 'backup_path' => null, 'error' => 'GitHub repository not configured.'];
        }

        $backupPath     = null;
        $archivePath    = null;
        $extractRootPath = null;

        try {
            $onProgress('preflight', 'Downloading release archive from GitHub…');
            $archivePath = $this->downloadRelease($repo, $versionTag, $token);

            $onProgress('backup', 'Extracting release archive…');
            $extracted       = $this->extractArchive($archivePath, $versionTag);
            $extractRootPath = $extracted['root'];
            $extractPath     = $extracted['source'];
            $manifest        = $this->buildDeploymentManifest($extractPath);

            if ((bool) config('updates.deployment.backup_before_deploy', true)) {
                $onProgress('backup', 'Creating code backup of current files…');
                $backupPath = $this->backupCurrentCode($manifest);
            }

            $onProgress('swap', 'Overwriting application files (no Apache restart required)…');
            $this->deployCode($extractPath, $manifest);

            // Clear stale caches before running composer so autoloader is clean.
            $this->runWithoutTenantContext(function (): void {
                foreach (self::ARTISAN_CLEAR_COMMANDS as $signature) {
                    $this->runArtisanCommand($signature);
                }
            });

            if ((bool) config('updates.deployment.run_composer_install', true)) {
                $onProgress('composer', 'Installing PHP dependencies (composer install --no-dev)…');
                $this->runRequiredShellCommand(
                    $this->composerInstallCommand(),
                    $this->stageHeartbeat($onHeartbeat, 'composer'),
                    $onLog,
                    'composer install'
                );
            }

            if ($this->shouldRunNpmBuild()) {
                $onProgress('npm', 'Installing Node.js dependencies…');
                $this->runRequiredShellCommand(
                    $this->npmInstallCommand(),
                    $this->stageHeartbeat($onHeartbeat, 'npm'),
                    $onLog,
                    'npm install'
                );
                $this->runRequiredShellCommand(
                    $this->npmAuditFixCommand(),
                    $this->stageHeartbeat($onHeartbeat, 'npm'),
                    $onLog,
                    'npm audit fix'
                );
                $onProgress('npm', 'Building frontend assets (npm run build)…');
                $this->runRequiredShellCommand(
                    'npm run build',
                    $this->stageHeartbeat($onHeartbeat, 'npm'),
                    $onLog,
                    'npm run build'
                );
            }

            $onProgress('cache', 'Rebuilding application caches…');
            $this->runWithoutTenantContext(function (): void {
                foreach (self::ARTISAN_CACHE_COMMANDS as $signature) {
                    $this->runArtisanCommand($signature);
                }
            });

            if ((bool) config('updates.deployment.run_queue_restart', true)) {
                try {
                    $this->runArtisanCommand('queue:restart');
                } catch (\Throwable) {
                    // Non-fatal — queue worker may not be running.
                }
            }

            Log::info('deployWithProgress completed.', ['version' => $versionTag]);

            return ['success' => true, 'backup_path' => $backupPath];

        } catch (\Throwable $e) {
            Log::error('deployWithProgress failed.', ['version' => $versionTag, 'error' => $e->getMessage()]);

            return ['success' => false, 'backup_path' => $backupPath, 'error' => $e->getMessage()];

        } finally {
            if ($archivePath !== null && File::exists($archivePath)) {
                File::delete($archivePath);
            }
            if ($extractRootPath !== null && File::exists($extractRootPath)) {
                File::deleteDirectory($extractRootPath);
            }
        }
    }

    /**
     * Return the ordered commands/checks used after code files are updated.
     *
     * @return array<int, string>
     */
    public function getDeploymentCommandPlan(?bool $centralContext = null): array
    {
        $centralContext ??= $this->isCentralContext();

        return array_map(
            static fn (array $step): string => $step['command'],
            $this->buildPostDeploymentStepPlan($centralContext)
        );
    }

    /**
     * Download release archive from GitHub.
     */
    private function downloadRelease(
        string $repo,
        string $versionTag,
        ?string $token,
        ?callable $onHeartbeat = null
    ): string
    {
        $this->validateVersionTag($versionTag);

        $url = "https://api.github.com/repos/{$repo}/zipball/{$versionTag}";
        $archivePath = "{$this->tempPath}/{$versionTag}.zip";

        if (File::exists($archivePath)) {
            File::delete($archivePath);
        }

        $lastProgressAt = 0.0;
        $request = Http::withHeaders([
            'Accept' => 'application/vnd.github.v3+json',
        ])->withOptions([
            'sink' => $archivePath,
            'progress' => function ($downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes) use (&$lastProgressAt, $onHeartbeat): void {
                if ($onHeartbeat === null) {
                    return;
                }

                $now = microtime(true);
                if ($now - $lastProgressAt < 5.0) {
                    return;
                }

                $onHeartbeat($this->downloadProgressMessage($downloadedBytes, $downloadTotal));
                $lastProgressAt = $now;
            },
        ])->connectTimeout(30)->timeout(600)->retry(3, 1000);

        if ($token) {
            $request = $request->withToken($token);
        }

        $response = $request->get($url);

        if (! $response->successful()) {
            if (File::exists($archivePath)) {
                File::delete($archivePath);
            }

            throw new RuntimeException("Failed to download release [{$versionTag}] (HTTP {$response->status()}).");
        }

        if (! File::exists($archivePath) || File::size($archivePath) === 0) {
            throw new RuntimeException("Release archive [{$versionTag}] downloaded but is empty.");
        }

        return $archivePath;
    }

    /**
     * Reject tags that contain anything other than a semver-like token.
     * Guards against path traversal when the tag is interpolated into
     * filesystem paths in downloadRelease/extractArchive/stageUpdate.
     */
    private function validateVersionTag(string $versionTag): void
    {
        if (! preg_match(self::VERSION_TAG_PATTERN, $versionTag)) {
            throw new RuntimeException("Invalid version tag [{$versionTag}]. Expected semver like v1.2.3.");
        }
    }

    /**
     * Extract downloaded archive.
     *
     * @return array{root: string, source: string}
     */
    private function extractArchive(string $archivePath, string $versionTag): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive extension is not available.');
        }

        $extractPath = "{$this->tempPath}/extract_{$versionTag}";

        if (File::exists($extractPath)) {
            File::deleteDirectory($extractPath);
        }

        File::makeDirectory($extractPath, 0755, true);
        
        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new \Exception("Failed to open archive");
        }
        
        $zip->extractTo($extractPath);
        $zip->close();
        
        // GitHub creates a subdirectory, find it
        $dirs = File::directories($extractPath);
        $sourcePath = count($dirs) === 1 ? $dirs[0] : $extractPath;

        return [
            'root' => $extractPath,
            'source' => $sourcePath,
        ];
    }

    /**
     * Backup current code before deployment.
     */
    private function backupCurrentCode(array $manifest): string
    {
        $timestamp = now()->format('Y-m-d_His');
        $backupPath = storage_path("app/deployments/backups/code_backup_{$timestamp}");
        
        File::makeDirectory($backupPath, 0755, true);
        
        // Backup directories that are replaced during deployment
        $criticalDirs = $manifest['directories'];
        
        foreach ($criticalDirs as $dir) {
            $source = base_path($dir);
            $destination = "{$backupPath}/{$dir}";
            
            if (File::exists($source)) {
                if (! File::copyDirectory($source, $destination)) {
                    throw new RuntimeException("Failed to back up directory [{$dir}] before deployment.");
                }
            }
        }
        
        // Backup managed root-level files from the deployment manifest
        foreach ($manifest['files'] as $file) {
            $source = base_path($file);
            $destination = "{$backupPath}/{$file}";

            if (File::exists($source)) {
                if (! File::copy($source, $destination)) {
                    throw new RuntimeException("Failed to back up file [{$file}] before deployment.");
                }
            }
        }
        
        return $backupPath;
    }

    /**
     * Deploy new code to application directory.
     */
    private function deployCode(string $sourcePath, array $manifest): void
    {
        $this->syncCodeTree($sourcePath, base_path(), $manifest, 'deployment');
    }

    /**
     * Run post-deployment tasks.
     */
    private function runPostDeploymentTasks(): array
    {
        $steps = $this->buildPostDeploymentStepPlan($this->isCentralContext());

        Log::info('Running post-deployment command plan.', [
            'commands' => array_map(static fn (array $step): string => $step['command'], $steps),
        ]);

        $this->runWithoutTenantContext(function () use ($steps): void {
            foreach ($steps as $step) {
                $this->executeDeploymentStep($step);
            }
        });

        return array_map(static fn (array $step): string => $step['command'], $steps);
    }

    /**
     * Determine whether deployment is running on central context.
     */
    private function isCentralContext(): bool
    {
        if (! function_exists('tenancy')) {
            return true;
        }

        return ! tenancy()->initialized;
    }

    /**
     * Execute shared deployment tasks without a tenant-scoped cache/database context.
     */
    private function runWithoutTenantContext(callable $callback): mixed
    {
        if (! function_exists('tenancy') || ! tenancy()->initialized) {
            return $callback();
        }

        $previousTenant = tenant();

        tenancy()->end();

        try {
            return $callback();
        } finally {
            if ($previousTenant) {
                tenancy()->initialize($previousTenant);
            }
        }
    }

    /**
     * Run a shell command and capture output.
     */
    private function runShellCommand(
        string $command,
        ?callable $onHeartbeat = null,
        ?callable $onOutput = null,
        ?string $label = null
    ): array
    {
        Log::info('Running deployment shell command.', ['command' => $command]);

        $stdoutPath = tempnam($this->tempPath, 'deploy-out-');
        $stderrPath = tempnam($this->tempPath, 'deploy-err-');

        if ($stdoutPath === false || $stderrPath === false) {
            throw new RuntimeException('Failed to allocate temp files for deployment command output.');
        }

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['file', $stdoutPath, 'w'],
            2 => ['file', $stderrPath, 'w'],
        ];

        $pipes = [];
        try {
            $process = @proc_open($command, $descriptorSpec, $pipes, base_path());

            if (! is_resource($process)) {
                throw new RuntimeException("Failed to start shell command [{$command}].");
            }

            if (isset($pipes[0])) {
                fclose($pipes[0]);
            }

            $output = '';
            $linesRead = 0;
            $lastLine = '';
            $exitCode = null;
            $lastHeartbeat = microtime(true);
            $heartbeatLabel = $label ?? $command;
            $stdoutOffset = 0;
            $stderrOffset = 0;

            while (true) {
                foreach ([[$stdoutPath, &$stdoutOffset], [$stderrPath, &$stderrOffset]] as [$path, &$offset]) {
                    $chunk = $this->readProcessOutputChunk($path, $offset);
                    if ($chunk === '') {
                        continue;
                    }

                    $output .= $chunk;
                    $linesRead += substr_count($chunk, "\n");

                    $candidate = $this->extractLastLine($chunk);
                    if ($candidate !== null) {
                        $lastLine = $candidate;
                    }

                    if ($onOutput !== null) {
                        $onOutput($chunk);
                    }
                }

                $status = proc_get_status($process);
                if (! $status['running']) {
                    $exitCode = $status['exitcode'];
                    break;
                }

                $now = microtime(true);
                if ($onHeartbeat !== null && ($now - $lastHeartbeat) >= 5.0) {
                    $message = $lastLine !== ''
                        ? sprintf('%s - %s', $heartbeatLabel, $this->truncateValue($lastLine, 160))
                        : sprintf('%s - running (%d log lines)', $heartbeatLabel, $linesRead);

                    $onHeartbeat($message);
                    $lastHeartbeat = $now;
                }

                usleep(200000);
            }

            foreach ([[$stdoutPath, &$stdoutOffset], [$stderrPath, &$stderrOffset]] as [$path, &$offset]) {
                $chunk = $this->readProcessOutputChunk($path, $offset);
                if ($chunk === '') {
                    continue;
                }

                $output .= $chunk;

                $candidate = $this->extractLastLine($chunk);
                if ($candidate !== null) {
                    $lastLine = $candidate;
                }

                if ($onOutput !== null) {
                    $onOutput($chunk);
                }
            }

            $closeCode = proc_close($process);
            $exit = $exitCode ?? $closeCode;
            $trimmedOutput = trim($output);
        } finally {
            if (is_file($stdoutPath)) {
                @unlink($stdoutPath);
            }

            if (is_file($stderrPath)) {
                @unlink($stderrPath);
            }
        }

        Log::info('Deployment shell command finished.', [
            'command' => $command,
            'exit_code' => $exit,
        ]);

        return [
            'exit_code' => $exit,
            'output' => $trimmedOutput,
        ];
    }

    /**
     * Rollback to previous code version.
     */
    public function rollbackCode(string $backupPath): array
    {
        try {
            // Restore from backup
            $manifest = $this->buildDeploymentManifest($backupPath);

            $this->syncCodeTree($backupPath, base_path(), $manifest, 'rollback');
            
            // Run post-deployment tasks
            $postDeploymentCommands = $this->runPostDeploymentTasks();
            
            Log::info("Code rolled back successfully", ['backup' => $backupPath]);
            
            return [
                'success' => true,
                'post_deployment_commands' => $postDeploymentCommands,
            ];
            
        } catch (\Exception $e) {
            Log::error("Code rollback failed", ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Stage an update for the external updater to apply.
     *
     * Downloads the release zip, extracts it into storage/app/deployments/
     * pending/<version>/, and writes a config.json next to it. The Laravel
     * app stays online through this entire step — the file swap happens
     * later in applyStagedUpdate() via the out-of-process updater.
     *
     * @return array{pending_dir: string, config_path: string, backup_dir: string, manifest: array}
     */
    public function stageUpdate(string $versionTag): array
    {
        set_time_limit(0);

        $this->validateVersionTag($versionTag);

        $repo = config('services.github.repo');
        if (! $repo) {
            throw new RuntimeException('GitHub repository not configured.');
        }

        $token = config('services.github.token');

        $pendingDir = storage_path("app/deployments/pending/{$versionTag}");
        if (File::exists($pendingDir)) {
            File::deleteDirectory($pendingDir);
        }

        $archivePath = null;
        $extractRootPath = null;

        try {
            $archivePath = $this->downloadRelease($repo, $versionTag, $token);

            $extracted = $this->extractArchive($archivePath, $versionTag);
            $extractRootPath = $extracted['root'];
            $extractedSource = $extracted['source'];

            File::ensureDirectoryExists($pendingDir);

            // Move the extracted source into the durable pending dir. We copy
            // (not rename) because extracted source is inside the temp dir
            // that we'll clean up next.
            if (! File::copyDirectory($extractedSource, $pendingDir)) {
                throw new RuntimeException("Failed to move extracted release into [{$pendingDir}].");
            }

            $newManifest = $this->buildDeploymentManifest($pendingDir);
            $oldManifest = $this->buildDeploymentManifest(base_path());
            $unionManifest = $this->unionManifests($newManifest, $oldManifest);

            $timestamp = now()->format('Ymd_His');
            $backupDir = storage_path("app/deployments/backups/pre_{$versionTag}_{$timestamp}");

            $configPath = $pendingDir . DIRECTORY_SEPARATOR . 'updater-config.json';
            $this->writeUpdaterConfig($configPath, [
                'pending_dir' => $pendingDir,
                'backup_dir' => $backupDir,
                'manifest' => $unionManifest,
                'target_version' => $versionTag,
            ]);

            Log::info('Update staged successfully.', [
                'version' => $versionTag,
                'pending_dir' => $pendingDir,
                'config_path' => $configPath,
            ]);

            return [
                'pending_dir' => $pendingDir,
                'config_path' => $configPath,
                'backup_dir' => $backupDir,
                'manifest' => $unionManifest,
            ];
        } catch (\Throwable $e) {
            if (File::exists($pendingDir)) {
                File::deleteDirectory($pendingDir);
            }

            throw $e;
        } finally {
            if ($archivePath !== null && File::exists($archivePath)) {
                File::delete($archivePath);
            }
            if ($extractRootPath !== null && File::exists($extractRootPath)) {
                File::deleteDirectory($extractRootPath);
            }
        }
    }

    /**
     * Launch updater/apply.bat detached so it runs after this PHP request
     * returns. The caller should immediately redirect the browser to a
     * status page that polls storage/app/deployments/status.json.
     *
     * @return array{launched: bool, command: string}
     */
    public function applyStagedUpdate(string $configPath): array
    {
        if (! File::exists($configPath)) {
            throw new RuntimeException("Updater config not found: [{$configPath}].");
        }

        if (! (bool) config('updates.updater.enabled', true)) {
            throw new RuntimeException('External updater is disabled via UPDATER_ENABLED.');
        }

        $applyBat = base_path('updater' . DIRECTORY_SEPARATOR . 'apply.bat');
        if (! File::exists($applyBat)) {
            throw new RuntimeException("Updater launcher not found at [{$applyBat}]. Is the updater/ directory deployed?");
        }

        // `start "" /B` spawns the child detached and returns immediately. popen
        // launches cmd.exe, which runs start, start launches apply.bat, then
        // cmd.exe exits — pclose returns in ~100ms while apply.bat runs on.
        $command = sprintf(
            'start "LaundryUpdater" /B "%s" "%s"',
            $applyBat,
            $configPath
        );

        Log::info('Launching external updater.', [
            'command' => $command,
            'config' => $configPath,
        ]);

        $handle = popen($command, 'r');
        if ($handle === false) {
            throw new RuntimeException('Failed to launch external updater.');
        }
        pclose($handle);

        return [
            'launched' => true,
            'command' => $command,
        ];
    }

    /**
     * Build the config.json consumed by updater/apply.php.
     *
     * @param array{pending_dir: string, backup_dir: string, manifest: array, target_version: string} $stage
     */
    private function writeUpdaterConfig(string $configPath, array $stage): void
    {
        $updaterConfig = config('updates.updater', []);
        $deploymentConfig = config('updates.deployment', []);

        $payload = [
            'target_version' => $stage['target_version'],
            'app_root' => base_path(),
            'pending_dir' => $stage['pending_dir'],
            'previous_backup_dir' => $stage['backup_dir'],
            'status_file' => storage_path('app/deployments/status.json'),
            'log_file' => storage_path('logs/updater.log'),
            'manifest' => $stage['manifest'],

            'php_binary' => $updaterConfig['php_binary'] ?? PHP_BINARY,
            'composer_binary' => $updaterConfig['composer_binary'] ?? 'composer',
            'npm_binary' => $updaterConfig['npm_binary'] ?? 'npm',
            'apache_service' => $updaterConfig['apache_service'] ?? null,
            'apache_start_bat' => $updaterConfig['apache_start_bat'] ?? null,
            'min_free_space_mb' => (int) ($updaterConfig['min_free_space_mb'] ?? 500),

            'run_composer' => (bool) ($deploymentConfig['run_composer_install'] ?? true),
            'run_npm_build' => $this->shouldRunNpmBuild(),
            'run_migrations' => (bool) ($deploymentConfig['run_database_migrations'] ?? true),
            'run_tenant_migrations' => (bool) ($deploymentConfig['run_tenant_migrations'] ?? true),
        ];

        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new RuntimeException('Failed to encode updater config as JSON.');
        }

        if (File::put($configPath, $encoded) === false) {
            throw new RuntimeException("Failed to write updater config to [{$configPath}].");
        }
    }

    /**
     * Union of two deployment manifests — ensures we sync directories and
     * files present in either the old or the new tree, so top-level entries
     * removed in the new release still get cleaned up.
     *
     * @param array{directories: array<int, string>, files: array<int, string>} $a
     * @param array{directories: array<int, string>, files: array<int, string>} $b
     * @return array{directories: array<int, string>, files: array<int, string>}
     */
    private function unionManifests(array $a, array $b): array
    {
        $directories = array_values(array_unique(array_merge($a['directories'], $b['directories'])));
        $files = array_values(array_unique(array_merge($a['files'], $b['files'])));

        sort($directories);
        sort($files);

        return [
            'directories' => $directories,
            'files' => $files,
        ];
    }

    /**
     * Check if deployment is safe.
     */
    public function canDeploy(): array
    {
        $checks = [];
        $runComposerInstall = (bool) config('updates.deployment.run_composer_install', true);
        $runNpmBuild = $this->shouldRunNpmBuild();
        
        // Check disk space
        $freeSpace = disk_free_space(base_path());
        $requiredSpace = max((int) config('updates.deployment.min_disk_space_mb', 500), 1) * 1024 * 1024;
        
        $checks['disk_space'] = [
            'passed' => is_numeric($freeSpace) && $freeSpace > $requiredSpace,
            'message' => is_numeric($freeSpace) && $freeSpace > $requiredSpace
                ? 'Sufficient disk space'
                : 'Insufficient disk space'
        ];
        
        // Check write permissions
        $checks['permissions'] = [
            'passed' => is_writable(base_path()),
            'message' => is_writable(base_path()) 
                ? 'Directory is writable' 
                : 'Directory is not writable'
        ];
        
        $checks['github_repo'] = [
            'passed' => trim((string) config('services.github.repo', '')) !== '',
            'message' => trim((string) config('services.github.repo', '')) !== ''
                ? 'GitHub repository is configured'
                : 'GitHub repository is not configured'
        ];

        $checks['zip_extension'] = [
            'passed' => class_exists(ZipArchive::class),
            'message' => class_exists(ZipArchive::class)
                ? 'ZipArchive extension is available'
                : 'ZipArchive extension is not available'
        ];

        if ($runComposerInstall) {
            $composerAvailable = $this->commandIsAvailable('composer --version');
            $checks['composer'] = [
                'passed' => $composerAvailable,
                'message' => $composerAvailable
                    ? 'Composer is available'
                    : 'Composer is not available'
            ];
        }

        if ($runNpmBuild) {
            $npmAvailable = $this->commandIsAvailable('npm --version');
            $checks['npm'] = [
                'passed' => $npmAvailable,
                'message' => $npmAvailable
                    ? 'NPM is available'
                    : 'NPM is not available'
            ];
        }
        
        $allPassed = collect($checks)->every(fn($check) => $check['passed']);
        
        return [
            'can_deploy' => $allPassed,
            'checks' => $checks,
            'planned_commands' => $this->getDeploymentCommandPlan(),
        ];
    }

    /**
     * Build the step plan executed after code files are synced.
     *
     * @return array<int, array{type: string, command: string, signature?: string, parameters?: array<string, mixed>, shell?: string}>
     */
    private function buildPostDeploymentStepPlan(bool $centralContext): array
    {
        $steps = [];

        foreach (self::ARTISAN_CLEAR_COMMANDS as $signature) {
            $steps[] = $this->artisanStep($signature);
        }

        if ((bool) config('updates.deployment.run_composer_install', true)) {
            $steps[] = $this->shellStep($this->composerInstallCommand());
        }

        if ($this->shouldRunNpmBuild()) {
            $steps[] = $this->shellStep($this->npmInstallCommand());
            $steps[] = [
                'type' => 'check',
                'command' => 'verify frontend dependencies',
            ];
            $steps[] = $this->shellStep($this->npmAuditFixCommand());
            $steps[] = $this->shellStep('npm run build');
            $steps[] = [
                'type' => 'check',
                'command' => 'verify public/build/manifest.json',
            ];
        }

        if ($centralContext && (bool) config('updates.deployment.run_database_migrations', true)) {
            $steps[] = $this->artisanStep('migrate', ['--force' => true]);
        }

        if ($centralContext && (bool) config('updates.deployment.run_tenant_migrations', true)) {
            $steps[] = $this->artisanStep('tenants:migrate', ['--force' => true]);
        }

        foreach (self::ARTISAN_CACHE_COMMANDS as $signature) {
            $steps[] = $this->artisanStep($signature);
        }

        if ($centralContext && (bool) config('updates.deployment.run_queue_restart', true)) {
            $steps[] = $this->artisanStep('queue:restart');
        }

        return $steps;
    }

    /**
     * Execute a planned deployment step.
     *
     * @param array{type: string, command: string, signature?: string, parameters?: array<string, mixed>, shell?: string} $step
     */
    private function executeDeploymentStep(array $step): void
    {
        match ($step['type']) {
            'artisan' => $this->runArtisanCommand($step['signature'] ?? '', $step['parameters'] ?? []),
            'shell' => $this->runRequiredShellCommand($step['shell'] ?? $step['command']),
            'check' => $this->runDeploymentCheck($step['command']),
            default => throw new RuntimeException("Unknown deployment step type [{$step['type']}]."),
        };
    }

    /**
     * Create a normalized artisan step descriptor.
     *
     * @param array<string, mixed> $parameters
     * @return array{type: string, command: string, signature: string, parameters: array<string, mixed>}
     */
    private function artisanStep(string $signature, array $parameters = []): array
    {
        return [
            'type' => 'artisan',
            'command' => $this->formatArtisanCommand($signature, $parameters),
            'signature' => $signature,
            'parameters' => $parameters,
        ];
    }

    /**
     * Create a normalized shell step descriptor.
     *
     * @return array{type: string, command: string, shell: string}
     */
    private function shellStep(string $command): array
    {
        return [
            'type' => 'shell',
            'command' => $command,
            'shell' => $command,
        ];
    }

    /**
     * Run a required shell command and throw on failure.
     */
    private function runRequiredShellCommand(
        string $command,
        ?callable $onHeartbeat = null,
        ?callable $onOutput = null,
        ?string $label = null
    ): void
    {
        $result = $this->runShellCommand($command, $onHeartbeat, $onOutput, $label);

        if ($result['exit_code'] !== 0) {
            throw new RuntimeException("Command [{$command}] failed: " . ($result['output'] ?: 'unknown error'));
        }
    }

    /**
     * Bind a heartbeat callback to a specific stage name.
     */
    private function stageHeartbeat(?callable $onHeartbeat, string $stage): ?callable
    {
        if ($onHeartbeat === null) {
            return null;
        }

        return fn (string $message) => $onHeartbeat($stage, $message);
    }

    private function readProcessOutputChunk(string $path, int &$offset): string
    {
        clearstatcache(true, $path);

        $size = @filesize($path);
        if ($size === false || $size <= $offset) {
            return '';
        }

        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return '';
        }

        @fseek($handle, $offset);
        $chunk = @stream_get_contents($handle);
        @fclose($handle);

        if (! is_string($chunk) || $chunk === '') {
            return '';
        }

        $offset += strlen($chunk);

        return $chunk;
    }

    private function extractLastLine(string $chunk): ?string
    {
        $trimmed = rtrim($chunk, "\r\n");
        if ($trimmed === '') {
            return null;
        }

        $position = strrpos($trimmed, "\n");
        $line = $position === false ? $trimmed : substr($trimmed, $position + 1);
        $line = trim($line);

        return $line === '' ? null : $line;
    }

    private function truncateValue(string $value, int $max): string
    {
        return strlen($value) <= $max ? $value : substr($value, 0, $max - 3) . '...';
    }

    private function downloadProgressMessage($downloadedBytes, $downloadTotal): string
    {
        $downloaded = max(0, (int) round((float) $downloadedBytes));
        $total = max(0, (int) round((float) $downloadTotal));

        if ($total > 0) {
            $percent = min(100, (int) floor(($downloaded / $total) * 100));

            return sprintf(
                'Downloading release archive from GitHub... %d%% (%s / %s)',
                $percent,
                $this->formatBytes($downloaded),
                $this->formatBytes($total)
            );
        }

        return sprintf(
            'Downloading release archive from GitHub... %s received',
            $this->formatBytes($downloaded)
        );
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = max($bytes, 0);
        $unit = 0;

        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        return $unit === 0
            ? sprintf('%d %s', (int) round($value), $units[$unit])
            : sprintf('%.1f %s', $value, $units[$unit]);
    }

    /**
     * Execute an artisan command and throw on failure.
     *
     * @param array<string, mixed> $parameters
     */
    private function runArtisanCommand(string $signature, array $parameters = []): void
    {
        Log::info('Running deployment artisan command.', [
            'command' => $this->formatArtisanCommand($signature, $parameters),
        ]);

        $exitCode = Artisan::call($signature, $parameters);
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            throw new RuntimeException(
                "Artisan command [{$signature}] failed: " . ($output !== '' ? $output : "exit code {$exitCode}")
            );
        }
    }

    /**
     * Run a deployment verification step.
     */
    private function runDeploymentCheck(string $command): void
    {
        if ($command === 'verify frontend dependencies') {
            $requiredFiles = [
                base_path('node_modules/tailwindcss/lib/css/preflight.css'),
                base_path('node_modules/laravel-vite-plugin/dist/dev-server-index.html'),
            ];

            foreach ($requiredFiles as $path) {
                if (! File::exists($path)) {
                    throw new RuntimeException("Frontend dependency verification failed: missing [{$path}] after npm install.");
                }
            }

            return;
        }

        if ($command === 'verify public/build/manifest.json') {
            if (! File::exists(public_path('build/manifest.json'))) {
                throw new RuntimeException('Vite build manifest missing after build.');
            }

            return;
        }

        throw new RuntimeException("Unknown deployment check [{$command}].");
    }

    /**
     * Format a readable artisan command for logs and UI.
     *
     * @param array<string, mixed> $parameters
     */
    private function formatArtisanCommand(string $signature, array $parameters = []): string
    {
        $parts = ['php artisan', $signature];

        foreach ($parameters as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $parts[] = $key;
                }

                continue;
            }

            $parts[] = $key . '=' . $value;
        }

        return implode(' ', $parts);
    }

    /**
     * Determine if npm build steps should run for this application.
     */
    private function shouldRunNpmBuild(): bool
    {
        return (bool) config('updates.deployment.run_npm_build', true)
            && File::exists(base_path('package.json'));
    }

    /**
     * Build the Composer install command used during deployment.
     */
    private function composerInstallCommand(): string
    {
        return 'composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader';
    }

    /**
     * Build the NPM dependency install command used during deployment.
     */
    private function npmInstallCommand(): string
    {
        return File::exists(base_path('package-lock.json'))
            ? 'npm ci --no-audit --no-fund'
            : 'npm install --no-audit --no-fund';
    }

    /**
     * Build the optional npm audit fix command used during deployment.
     */
    private function npmAuditFixCommand(): string
    {
        return 'npm audit fix --no-fund';
    }

    /**
     * Probe whether a command is available in the current runtime.
     */
    private function commandIsAvailable(string $command): bool
    {
        try {
            return $this->runShellCommand($command)['exit_code'] === 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Sync managed release files into the live application tree.
     *
     * @param array{directories: array<int, string>, files: array<int, string>} $manifest
     */
    private function syncCodeTree(string $sourceRoot, string $destinationRoot, array $manifest, string $operation): void
    {
        foreach ($manifest['directories'] as $dir) {
            $source = "{$sourceRoot}/{$dir}";
            $destination = "{$destinationRoot}/{$dir}";

            if (! is_dir($source)) {
                continue;
            }

            $this->syncManagedDirectory($source, $destination, $dir, $operation);
        }

        foreach ($manifest['files'] as $file) {
            $source = "{$sourceRoot}/{$file}";
            $destination = "{$destinationRoot}/{$file}";

            if (! is_file($source)) {
                continue;
            }

            $this->syncManagedFile($source, $destination, $file, $operation);
        }
    }

    /**
     * Sync a managed top-level directory without deleting the live copy first.
     */
    private function syncManagedDirectory(
        string $source,
        string $destination,
        string $relativePath,
        string $operation
    ): void {
        if (is_file($destination)) {
            $this->deleteManagedPath($destination, $relativePath, $operation);
        }

        File::ensureDirectoryExists($destination);

        $this->syncDirectoryContents($source, $destination, $relativePath, $operation);
    }

    /**
     * Recursively sync directory contents and remove stale entries afterwards.
     */
    private function syncDirectoryContents(
        string $sourceDirectory,
        string $destinationDirectory,
        string $relativePath,
        string $operation
    ): void {
        foreach ($this->listDirectoryEntries($sourceDirectory) as $entry) {
            $sourcePath = "{$sourceDirectory}/{$entry}";
            $destinationPath = "{$destinationDirectory}/{$entry}";
            $entryRelativePath = trim("{$relativePath}/{$entry}", '/');

            if (is_dir($sourcePath)) {
                if (is_file($destinationPath)) {
                    $this->deleteManagedPath($destinationPath, $entryRelativePath, $operation);
                }

                File::ensureDirectoryExists($destinationPath);
                $this->syncDirectoryContents($sourcePath, $destinationPath, $entryRelativePath, $operation);

                continue;
            }

            if (is_dir($destinationPath)) {
                $this->deleteManagedPath($destinationPath, $entryRelativePath, $operation);
            }

            $this->copyManagedFile($sourcePath, $destinationPath, $entryRelativePath, $operation);
        }

        foreach ($this->listDirectoryEntries($destinationDirectory) as $entry) {
            $sourcePath = "{$sourceDirectory}/{$entry}";

            if (file_exists($sourcePath)) {
                continue;
            }

            $destinationPath = "{$destinationDirectory}/{$entry}";
            $entryRelativePath = trim("{$relativePath}/{$entry}", '/');

            $this->deleteManagedPath($destinationPath, $entryRelativePath, $operation);
        }
    }

    /**
     * Copy a managed root-level file into place.
     */
    private function syncManagedFile(
        string $source,
        string $destination,
        string $relativePath,
        string $operation
    ): void {
        if (is_dir($destination)) {
            $this->deleteManagedPath($destination, $relativePath, $operation);
        }

        $this->copyManagedFile($source, $destination, $relativePath, $operation);
    }

    /**
     * Copy a file and fail loudly if the filesystem operation does not succeed.
     */
    private function copyManagedFile(
        string $source,
        string $destination,
        string $relativePath,
        string $operation
    ): void {
        File::ensureDirectoryExists(dirname($destination));

        if (! File::copy($source, $destination)) {
            throw new RuntimeException(
                "Failed to copy file [{$relativePath}] during {$operation}."
            );
        }
    }

    /**
     * Delete a managed path and fail if the live install cannot be updated safely.
     */
    private function deleteManagedPath(string $path, string $relativePath, string $operation): void
    {
        if (is_dir($path)) {
            if (! File::deleteDirectory($path)) {
                throw new RuntimeException(
                    "Failed to remove directory [{$relativePath}] during {$operation}."
                );
            }

            return;
        }

        if (file_exists($path) && ! File::delete($path)) {
            throw new RuntimeException(
                "Failed to remove file [{$relativePath}] during {$operation}."
            );
        }
    }

    /**
     * Return direct children for a directory, including dotfiles.
     *
     * @return array<int, string>
     */
    private function listDirectoryEntries(string $path): array
    {
        $entries = scandir($path);

        if ($entries === false) {
            throw new RuntimeException("Failed to read directory [{$path}] during deployment sync.");
        }

        return array_values(array_filter($entries, static fn (string $entry) => $entry !== '.' && $entry !== '..'));
    }

    /**
     * Resolve the deployment manifest from a release or backup root.
     *
     * @return array{directories: array<int, string>, files: array<int, string>}
     */
    private function buildDeploymentManifest(string $rootPath): array
    {
        $directories = [];
        $files = [];

        foreach (scandir($rootPath) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if ($this->shouldIgnoreDeploymentEntry($entry)) {
                continue;
            }

            $fullPath = "{$rootPath}/{$entry}";

            if (is_dir($fullPath)) {
                $directories[] = $entry;
                continue;
            }

            if (is_file($fullPath)) {
                $files[] = $entry;
            }
        }

        sort($directories);
        sort($files);

        if ($directories === [] && $files === []) {
            throw new RuntimeException("Deployment archive [{$rootPath}] did not contain any deployable files.");
        }

        Log::info('Resolved deployment manifest', [
            'root' => $rootPath,
            'directories' => $directories,
            'files' => $files,
        ]);

        return [
            'directories' => $directories,
            'files' => $files,
        ];
    }

    /**
     * Ignore local-only or unsafe repository roots during deployment.
     */
    private function shouldIgnoreDeploymentEntry(string $entry): bool
    {
        if (str_starts_with($entry, '.env')) {
            return true;
        }

        return in_array($entry, [
            '.git',
            '.github',
            '.idea',
            '.vscode',
            'node_modules',
            'storage',
            'vendor',
        ], true);
    }
}
