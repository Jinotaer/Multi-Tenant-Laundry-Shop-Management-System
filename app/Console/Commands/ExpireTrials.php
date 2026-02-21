<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class ExpireTrials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:expire-trials';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable tenants whose free trial has expired and have not paid';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredTenants = Tenant::query()
            ->where('is_enabled', true)
            ->where('is_paid', false)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->get();

        if ($expiredTenants->isEmpty()) {
            $this->info('No expired trials found.');

            return self::SUCCESS;
        }

        foreach ($expiredTenants as $tenant) {
            $shopName = $tenant->data['shop_name'] ?? $tenant->id;
            $tenant->update(['is_enabled' => false]);
            $this->line("Disabled: <comment>{$shopName}</comment> (trial expired {$tenant->trial_ends_at->diffForHumans()})");
        }

        $this->info("Disabled {$expiredTenants->count()} tenant(s) with expired trials.");

        return self::SUCCESS;
    }
}
