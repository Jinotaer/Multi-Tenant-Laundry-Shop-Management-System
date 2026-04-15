<?php

namespace App\Console\Commands;

use App\Services\GitHubReleaseService;
use Illuminate\Console\Command;

class SyncGitHubReleases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'releases:sync {--force : Force sync even if synced recently}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically sync GitHub releases and notify tenants of updates';

    /**
     * Execute the console command.
     */
    public function handle(GitHubReleaseService $service)
    {
        $this->info('Syncing GitHub releases...');

        $force = $this->option('force');
        $success = $service->syncReleases($force);

        if ($success) {
            $this->info('✓ GitHub releases synced successfully!');
            $this->info('✓ Tenants notified of available updates.');

            return Command::SUCCESS;
        }

        $this->error('✗ Failed to sync GitHub releases.');
        $this->warn('Check logs for more details.');

        return Command::FAILURE;
    }
}
