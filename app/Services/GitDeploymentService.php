<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class GitDeploymentService
{
    private string $repoPath;
    private string $repoUrl;

    public function __construct()
    {
        $this->repoPath = base_path();
        $this->repoUrl = config('services.github.repo_url'); // e.g., https://github.com/username/repo.git
    }

    /**
     * Deploy using git pull.
     */
    public function deployWithGitPull(string $versionTag): array
    {
        try {
            // Check if git is available
            if (!$this->isGitAvailable()) {
                return ['success' => false, 'error' => 'Git is not available'];
            }

            // Check if this is a git repository
            if (!$this->isGitRepository()) {
                return $this->cloneRepository($versionTag);
            }

            // Stash any local changes
            $this->executeGitCommand('git stash');

            // Fetch all tags and branches
            $this->executeGitCommand('git fetch --all --tags');

            // Checkout specific version tag
            $result = $this->executeGitCommand("git checkout tags/{$versionTag}");

            if ($result['exit_code'] !== 0) {
                throw new \Exception("Failed to checkout version: {$result['output']}");
            }

            // Pull latest changes
            $this->executeGitCommand('git pull origin main');

            // Update submodules if any
            $this->executeGitCommand('git submodule update --init --recursive');

            // Run post-deployment tasks
            $this->runPostDeploymentTasks();

            Log::info("Git deployment successful", ['version' => $versionTag]);

            return [
                'success' => true,
                'version' => $versionTag,
                'method' => 'git_pull'
            ];

        } catch (\Exception $e) {
            Log::error("Git deployment failed", [
                'version' => $versionTag,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clone repository if not exists.
     */
    private function cloneRepository(string $versionTag): array
    {
        try {
            $tempPath = storage_path('app/deployments/git_clone');

            if (File::exists($tempPath)) {
                File::deleteDirectory($tempPath);
            }

            File::makeDirectory($tempPath, 0755, true);

            // Clone repository
            $result = $this->executeGitCommand(
                "git clone --branch {$versionTag} {$this->repoUrl} {$tempPath}"
            );

            if ($result['exit_code'] !== 0) {
                throw new \Exception("Failed to clone repository: {$result['output']}");
            }

            // Copy files to application directory
            $this->copyFiles($tempPath, $this->repoPath);

            // Clean up
            File::deleteDirectory($tempPath);

            return [
                'success' => true,
                'version' => $versionTag,
                'method' => 'git_clone'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Execute git command.
     */
    private function executeGitCommand(string $command): array
    {
        $output = [];
        $exitCode = 0;

        exec("cd {$this->repoPath} && {$command} 2>&1", $output, $exitCode);

        return [
            'output' => implode("\n", $output),
            'exit_code' => $exitCode
        ];
    }

    /**
     * Check if git is available.
     */
    private function isGitAvailable(): bool
    {
        exec('git --version 2>&1', $output, $exitCode);
        return $exitCode === 0;
    }

    /**
     * Check if current directory is a git repository.
     */
    private function isGitRepository(): bool
    {
        return File::exists($this->repoPath . '/.git');
    }

    /**
     * Get current git version/tag.
     */
    public function getCurrentVersion(): ?string
    {
        if (!$this->isGitRepository()) {
            return null;
        }

        $result = $this->executeGitCommand('git describe --tags --exact-match 2>/dev/null');

        if ($result['exit_code'] === 0) {
            return trim($result['output']);
        }

        // Fallback to commit hash
        $result = $this->executeGitCommand('git rev-parse --short HEAD');
        return trim($result['output']);
    }

    /**
     * Get list of available tags.
     */
    public function getAvailableTags(): array
    {
        if (!$this->isGitRepository()) {
            return [];
        }

        $result = $this->executeGitCommand('git tag -l');

        if ($result['exit_code'] !== 0) {
            return [];
        }

        return array_filter(explode("\n", trim($result['output'])));
    }

    /**
     * Copy files from source to destination.
     */
    private function copyFiles(string $source, string $destination): void
    {
        $directories = ['app', 'config', 'database', 'routes', 'resources', 'public'];

        foreach ($directories as $dir) {
            $sourcePath = "{$source}/{$dir}";
            $destPath = "{$destination}/{$dir}";

            if (File::exists($sourcePath)) {
                if (File::exists($destPath)) {
                    File::deleteDirectory($destPath);
                }
                File::copyDirectory($sourcePath, $destPath);
            }
        }

        // Copy composer files
        if (File::exists("{$source}/composer.json")) {
            File::copy("{$source}/composer.json", "{$destination}/composer.json");
        }

        if (File::exists("{$source}/composer.lock")) {
            File::copy("{$source}/composer.lock", "{$destination}/composer.lock");
        }
    }

    /**
     * Run post-deployment tasks.
     */
    private function runPostDeploymentTasks(): void
    {
        // Install dependencies
        exec('composer install --no-dev --optimize-autoloader 2>&1', $output, $returnVar);

        // Clear caches
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        // Rebuild caches
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        // Run npm if needed
        if (File::exists(base_path('package.json'))) {
            exec('npm install && npm run build 2>&1');
        }
    }

    /**
     * Get git status.
     */
    public function getStatus(): array
    {
        if (!$this->isGitRepository()) {
            return ['is_repo' => false];
        }

        $statusResult = $this->executeGitCommand('git status --porcelain');
        $branchResult = $this->executeGitCommand('git rev-parse --abbrev-ref HEAD');
        $commitResult = $this->executeGitCommand('git rev-parse --short HEAD');

        return [
            'is_repo' => true,
            'current_branch' => trim($branchResult['output']),
            'current_commit' => trim($commitResult['output']),
            'current_version' => $this->getCurrentVersion(),
            'has_changes' => !empty(trim($statusResult['output'])),
            'changes' => trim($statusResult['output'])
        ];
    }
}
