<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

class CodeDeploymentService
{
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
        
        if (!$repo) {
            return ['success' => false, 'error' => 'GitHub repository not configured'];
        }
        
        try {
            // Download release archive
            $archivePath = $this->downloadRelease($repo, $versionTag, $token);
            
            // Extract archive
            $extractPath = $this->extractArchive($archivePath, $versionTag);
            $manifest = $this->buildDeploymentManifest($extractPath);
            
            // Backup current code
            $backupPath = $this->backupCurrentCode($manifest);
            
            // Deploy new code
            $this->deployCode($extractPath, $manifest);
            
            // Run post-deployment tasks
            $this->runPostDeploymentTasks();
            
            // Clean up
            File::delete($archivePath);
            File::deleteDirectory($extractPath);
            
            Log::info("Code deployed successfully", ['version' => $versionTag]);
            
            return [
                'success' => true,
                'version' => $versionTag,
                'backup_path' => $backupPath,
            ];
            
        } catch (\Exception $e) {
            Log::error("Code deployment failed", [
                'version' => $versionTag,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'backup_path' => $backupPath,
            ];
        }
    }

    /**
     * Download release archive from GitHub.
     */
    private function downloadRelease(string $repo, string $versionTag, ?string $token): string
    {
        $url = "https://api.github.com/repos/{$repo}/zipball/{$versionTag}";
        
        $request = Http::withHeaders([
            'Accept' => 'application/vnd.github.v3+json',
        ]);
        
        if ($token) {
            $request = $request->withToken($token);
        }
        
        $response = $request->get($url);
        
        if (!$response->successful()) {
            throw new \Exception("Failed to download release: " . $response->body());
        }
        
        $archivePath = "{$this->tempPath}/{$versionTag}.zip";
        File::put($archivePath, $response->body());
        
        return $archivePath;
    }

    /**
     * Extract downloaded archive.
     */
    private function extractArchive(string $archivePath, string $versionTag): string
    {
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
        if (count($dirs) === 1) {
            return $dirs[0];
        }
        
        return $extractPath;
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
                File::copyDirectory($source, $destination);
            }
        }
        
        // Backup managed root-level files from the deployment manifest
        foreach ($manifest['files'] as $file) {
            $source = base_path($file);
            $destination = "{$backupPath}/{$file}";

            if (File::exists($source)) {
                File::copy($source, $destination);
            }
        }
        
        return $backupPath;
    }

    /**
     * Deploy new code to application directory.
     */
    private function deployCode(string $sourcePath, array $manifest): void
    {
        foreach ($manifest['directories'] as $dir) {
            $source = "{$sourcePath}/{$dir}";
            $destination = base_path($dir);
            
            if (!File::exists($source)) {
                continue;
            }

            $this->guardAgainstUnexpectedlyEmptySource($dir, $source, $destination);
            $this->replaceDirectory($source, $destination, 'deployment');
        }
        
        // Sync root-level files
        foreach ($manifest['files'] as $file) {
            $source = "{$sourcePath}/{$file}";
            $destination = base_path($file);

            if (File::exists($source)) {
                if (!File::copy($source, $destination)) {
                    throw new RuntimeException("Failed to copy file [{$file}] during deployment.");
                }

                continue;
            }

            if (File::exists($destination)) {
                File::delete($destination);
            }
        }
    }

    /**
     * Run post-deployment tasks.
     */
    private function runPostDeploymentTasks(): void
    {
        // Clear caches
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        // Install composer dependencies
        if ((bool) config('updates.deployment.run_composer_install', true)) {
            $composerResult = $this->runShellCommand('composer install --no-dev --optimize-autoloader');

            if ($composerResult['exit_code'] !== 0) {
                throw new RuntimeException('Composer install failed: ' . $composerResult['output']);
            }
        }

        // Rebuild caches
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        // Run npm build if needed
        if ((bool) config('updates.deployment.run_npm_build', true) && File::exists(base_path('package.json'))) {
            $npmInstallCommand = File::exists(base_path('package-lock.json'))
                ? 'npm ci --no-audit --no-fund'
                : 'npm install --no-audit --no-fund';

            $npmInstallResult = $this->runShellCommand($npmInstallCommand);

            if ($npmInstallResult['exit_code'] !== 0) {
                throw new RuntimeException('NPM install failed: ' . $npmInstallResult['output']);
            }

            $npmBuildResult = $this->runShellCommand('npm run build');

            if ($npmBuildResult['exit_code'] !== 0) {
                throw new RuntimeException('NPM build failed: ' . $npmBuildResult['output']);
            }

            if (! File::exists(public_path('build/manifest.json'))) {
                throw new RuntimeException('Vite build manifest missing after build.');
            }
        }

        // Run framework/database tasks automatically when deployment runs in central context.
        if ($this->isCentralContext()) {
            if ((bool) config('updates.deployment.run_database_migrations', true)) {
                $migrateExit = Artisan::call('migrate', ['--force' => true]);

                if ($migrateExit !== 0) {
                    throw new RuntimeException('Central migration failed: ' . trim(Artisan::output()));
                }
            }

            if ((bool) config('updates.deployment.run_tenant_migrations', true)) {
                $tenantMigrateExit = Artisan::call('tenants:migrate', ['--force' => true]);

                if ($tenantMigrateExit !== 0) {
                    throw new RuntimeException('Tenant migrations failed: ' . trim(Artisan::output()));
                }
            }

            if ((bool) config('updates.deployment.run_queue_restart', true)) {
                Artisan::call('queue:restart');
            }
        }
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
     * Run a shell command and capture output.
     */
    private function runShellCommand(string $command): array
    {
        $output = [];
        $exitCode = 0;

        // Force execution strictly inside the application root folder
        // (Prevents failures if web server executes this script originating from the public/ folder)
        $basePath = escapeshellarg(base_path());
        $fullCommand = "cd {$basePath} && {$command}";

        exec($fullCommand . ' 2>&1', $output, $exitCode);

        return [
            'exit_code' => $exitCode,
            'output' => trim(implode(PHP_EOL, $output)),
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
            
            foreach ($manifest['directories'] as $dir) {
                $source = "{$backupPath}/{$dir}";
                $destination = base_path($dir);
                
                if (!File::exists($source)) {
                    continue;
                }

                $this->replaceDirectory($source, $destination, 'rollback');
            }
            
            // Restore root-level files
            foreach ($manifest['files'] as $file) {
                $source = "{$backupPath}/{$file}";
                $destination = base_path($file);

                if (File::exists($source)) {
                    if (!File::copy($source, $destination)) {
                        throw new RuntimeException("Failed to restore file [{$file}] during rollback.");
                    }

                    continue;
                }

                if (File::exists($destination)) {
                    File::delete($destination);
                }
            }
            
            // Run post-deployment tasks
            $this->runPostDeploymentTasks();
            
            Log::info("Code rolled back successfully", ['backup' => $backupPath]);
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            Log::error("Code rollback failed", ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check if deployment is safe.
     */
    public function canDeploy(): array
    {
        $checks = [];
        
        // Check disk space
        $freeSpace = disk_free_space(base_path());
        $requiredSpace = 500 * 1024 * 1024; // 500MB
        
        $checks['disk_space'] = [
            'passed' => $freeSpace > $requiredSpace,
            'message' => $freeSpace > $requiredSpace 
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
        
        // Check if composer is available
        exec('composer --version 2>&1', $output, $returnVar);
        $checks['composer'] = [
            'passed' => $returnVar === 0,
            'message' => $returnVar === 0 
                ? 'Composer is available' 
                : 'Composer is not available'
        ];
        
        $allPassed = collect($checks)->every(fn($check) => $check['passed']);
        
        return [
            'can_deploy' => $allPassed,
            'checks' => $checks
        ];
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

    /**
     * Avoid deleting a populated directory when release archive unexpectedly ships an empty one.
     */
    private function guardAgainstUnexpectedlyEmptySource(string $name, string $source, string $destination): void
    {
        if (!File::exists($destination)) {
            return;
        }

        $sourceFiles = $this->countFilesRecursively($source);
        $destinationFiles = $this->countFilesRecursively($destination);

        if ($destinationFiles > 0 && $sourceFiles === 0) {
            throw new RuntimeException("Unsafe deployment for [{$name}]: source archive directory is empty while destination has files.");
        }
    }

    /**
     * Stage a directory copy then swap, reducing risk of partial-copy loss.
     */
    private function replaceDirectory(string $source, string $destination, string $context): void
    {
        $tempDestination = $destination . '.__tmp_deploy_' . str_replace('.', '', uniqid('', true));

        try {
            if (File::exists($tempDestination)) {
                File::deleteDirectory($tempDestination);
            }

            if (!File::copyDirectory($source, $tempDestination)) {
                throw new RuntimeException("Failed to stage directory [{$source}] during {$context}.");
            }

            if (File::exists($destination)) {
                File::deleteDirectory($destination);
            }

            if (!File::moveDirectory($tempDestination, $destination, true)) {
                if (!File::copyDirectory($tempDestination, $destination)) {
                    throw new RuntimeException("Failed to activate staged directory [{$destination}] during {$context}.");
                }

                File::deleteDirectory($tempDestination);
            }
        } finally {
            if (File::exists($tempDestination)) {
                File::deleteDirectory($tempDestination);
            }
        }
    }

    /**
     * Count files in a directory recursively.
     */
    private function countFilesRecursively(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $count++;
            }
        }

        return $count;
    }
}
