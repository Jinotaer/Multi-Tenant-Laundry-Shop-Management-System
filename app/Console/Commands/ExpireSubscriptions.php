<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionExpired;
use App\Mail\SubscriptionRenewalReminder;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Expire paid subscriptions, send renewal reminders, and disable tenants after grace period';

    public function handle(): int
    {
        $this->info('Processing subscription expirations and reminders...');

        $this->sendRenewalReminders();
        $this->expireSubscriptions();
        $this->disableAfterGracePeriod();

        $this->info('Subscription processing complete.');

        return self::SUCCESS;
    }

    /**
     * Send renewal reminders at 7, 3, and 1 day before expiration.
     */
    protected function sendRenewalReminders(): void
    {
        $reminderDays = [7, 3, 1];

        foreach ($reminderDays as $days) {
            $targetDate = now()->addDays($days)->startOfDay();

            $tenants = Tenant::query()
                ->where('is_paid', true)
                ->where('is_enabled', true)
                ->whereNotNull('subscription_expires_at')
                ->whereDate('subscription_expires_at', $targetDate)
                ->where(function ($query) use ($days) {
                    $query->whereNull('last_renewal_reminder_sent_at')
                        ->orWhere('last_renewal_reminder_sent_at', '<', now()->subHours(12));
                })
                ->get();

            foreach ($tenants as $tenant) {
                $tenant->load('subscriptionPlan');
                $ownerEmail = $tenant->data['owner_email'] ?? null;

                if ($ownerEmail && $tenant->subscriptionPlan) {
                    try {
                        Mail::to($ownerEmail)->send(
                            new SubscriptionRenewalReminder($tenant, $days)
                        );

                        $tenant->update(['last_renewal_reminder_sent_at' => now()]);

                        $shopName = $tenant->data['shop_name'] ?? $tenant->id;
                        $this->line("Sent {$days}-day reminder to: <comment>{$shopName}</comment>");
                    } catch (\Exception $e) {
                        $this->error("Failed to send reminder to {$tenant->id}: {$e->getMessage()}");
                    }
                }
            }
        }
    }

    /**
     * Mark subscriptions as unpaid when they expire.
     */
    protected function expireSubscriptions(): void
    {
        $expiredTenants = Tenant::query()
            ->where('is_paid', true)
            ->where('is_enabled', true)
            ->whereNotNull('subscription_expires_at')
            ->where('subscription_expires_at', '<', now())
            ->get();

        foreach ($expiredTenants as $tenant) {
            $tenant->update(['is_paid' => false]);

            $shopName = $tenant->data['shop_name'] ?? $tenant->id;
            $this->line("Expired subscription: <comment>{$shopName}</comment>");

            // Send expiration notice
            $ownerEmail = $tenant->data['owner_email'] ?? null;
            if ($ownerEmail) {
                try {
                    Mail::to($ownerEmail)->send(
                        new SubscriptionExpired($tenant, $tenant->grace_period_days)
                    );
                } catch (\Exception $e) {
                    $this->error("Failed to send expiration notice to {$tenant->id}: {$e->getMessage()}");
                }
            }
        }

        if ($expiredTenants->isNotEmpty()) {
            $this->info("Expired {$expiredTenants->count()} subscription(s).");
        }
    }

    /**
     * Disable tenants after grace period ends.
     */
    protected function disableAfterGracePeriod(): void
    {
        $tenantsToDisable = Tenant::query()
            ->where('is_enabled', true)
            ->where('is_paid', false)
            ->whereNotNull('subscription_expires_at')
            ->whereRaw('DATE_ADD(subscription_expires_at, INTERVAL grace_period_days DAY) < ?', [now()])
            ->get();

        foreach ($tenantsToDisable as $tenant) {
            $tenant->update(['is_enabled' => false]);

            $shopName = $tenant->data['shop_name'] ?? $tenant->id;
            $this->line("Disabled after grace period: <comment>{$shopName}</comment>");
        }

        if ($tenantsToDisable->isNotEmpty()) {
            $this->info("Disabled {$tenantsToDisable->count()} tenant(s) after grace period.");
        }
    }
}
