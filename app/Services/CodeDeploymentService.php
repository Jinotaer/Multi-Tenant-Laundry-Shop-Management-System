<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
            
            // Backup current code
            $backupPath = $this->backupCurrentCode($versionTag);
            
            // Deploy new code
            $this->deployCode($extractPath);
            
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
            $request->withToken($token);
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
    private function backupCurrentCode(string $versionTag): string
    {
        $timestamp = now()->format('Y-m-d_His');
        $backupPath = storage_path("app/deployments/backups/code_backup_{$timestamp}");
        
        File::makeDirectory($backupPath, 0755, true);
        
        // Backup directories that are replaced during deployment
        $criticalDirs = $this->deploymentDirectories();
        
        foreach ($criticalDirs as $dir) {
            $source = base_path($dir);
            $destination = "{$backupPath}/{$dir}";
            
            if (File::exists($source)) {
                File::copyDirectory($source, $destination);
            }
        }
        
        // Backup root-level files that affect dependencies and builds
        foreach ($this->deploymentFiles() as $file) {
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
    private function deployCode(string $sourcePath): void
    {
        // Directories to deploy
        $deployDirs = $this->deploymentDirectories();
        
        foreach ($deployDirs as $dir) {
            $source = "{$sourcePath}/{$dir}";
            $destination = base_path($dir);
            
            if (!File::exists($source)) {
                continue;
            }
            
            // Remove old directory
            if (File::exists($destination)) {
                File::deleteDirectory($destination);
            }
            
            // Copy new directory
            File::copyDirectory($source, $destination);
        }
        
        // Sync root-level files
        foreach ($this->deploymentFiles() as $file) {
            $source = "{$sourcePath}/{$file}";
            $destination = base_path($file);

            if (File::exists($source)) {
                File::copy($source, $destination);
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
        exec('composer install --no-dev --optimize-autoloader 2>&1', $output, $returnVar);
        
        if ($returnVar !== 0) {
            Log::warning("Composer install had issues", ['output' => $output]);
        }
        
        // Rebuild caches
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
        
        // Run npm build if needed
        if (File::exists(base_path('package.json'))) {
            exec('npm install && npm run build 2>&1', $npmOutput, $npmReturn);
            
            if ($npmReturn !== 0) {
                Log::warning("NPM build had issues", ['output' => $npmOutput]);
            }
        }
    }

    /**
     * Rollback to previous code version.
     */
    public function rollbackCode(string $backupPath): array
    {
        try {
            // Restore from backup
            $deployDirs = $this->deploymentDirectories();
            
            foreach ($deployDirs as $dir) {
                $source = "{$backupPath}/{$dir}";
                $destination = base_path($dir);
                
                if (!File::exists($source)) {
                    continue;
                }
                
                if (File::exists($destination)) {
                    File::deleteDirectory($destination);
                }
                
                File::copyDirectory($source, $destination);
            }
            
            // Restore root-level files
            foreach ($this->deploymentFiles() as $file) {
                $source = "{$backupPath}/{$file}";
                $destination = base_path($file);

                if (File::exists($source)) {
                    File::copy($source, $destination);
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
     * Directories managed by code deployment.
     *
     * @return array<int, string>
     */
    private function deploymentDirectories(): array
    {
        return ['app', 'config', 'database', 'routes', 'resources', 'public'];
    }

    /**
     * Root-level files managed by code deployment.
     *
     * @return array<int, string>
     */
    private function deploymentFiles(): array
    {
        return [
            'composer.json',
            'composer.lock',
            'package.json',
            'package-lock.json',
            'vite.config.js',
            'postcss.config.js',
            'tailwind.config.js',
        ];
    }
}
