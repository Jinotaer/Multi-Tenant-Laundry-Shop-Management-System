<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GitHubReleaseService;

class SyncGitHubReleasesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-github-releases';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync the latest releases from the configured GitHub repository';

    /**
     * Execute the console command.
     */
    public function handle(GitHubReleaseService $service)
    {
        $this->info('Starting GitHub release sync...');
        
        $success = $service->syncReleases();
        
        if ($success) {
            $this->info('Successfully synced GitHub releases!');
            return Command::SUCCESS;
        } else {
            $this->error('Failed to sync GitHub releases. Check your logs.');
            return Command::FAILURE;
        }
    }
}
