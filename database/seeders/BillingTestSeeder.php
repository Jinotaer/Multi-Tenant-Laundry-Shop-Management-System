<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class BillingTestSeeder extends Seeder
{
    /**
     * Seed tenants with different billing states for testing.
     */
    public function run(): void
    {
        $starterPlan = SubscriptionPlan::where('name', 'Starter')->first();
        $premiumPlan = SubscriptionPlan::where('name', 'Premium')->first();

        if (!$starterPlan || !$premiumPlan) {
            $this->command->error('Subscription plans not found. Run DatabaseSeeder first.');
            return;
        }

        // 1. Tenant with payment expiring in 3 days
        $this->createTenantWithBilling(
            'expires-soon',
            'Expires Soon Shop',
            $premiumPlan,
            now()->subDays(27), // Paid 27 days ago, expires in 3 days
            'expires-soon@test.com',
            3 // invoices count
        );

        // 2. Tenant with payment expiring tomorrow
        $this->createTenantWithBilling(
            'expires-tomorrow',
            'Expires Tomorrow Shop',
            $premiumPlan,
            now()->subDays(29), // Paid 29 days ago, expires tomorrow
            'expires-tomorrow@test.com',
            2
        );

        // 3. Tenant with payment expiring today
        $this->createTenantWithBilling(
            'expires-today',
            'Expires Today Shop',
            $premiumPlan,
            now()->subDays(30), // Paid 30 days ago, expires today
            'expires-today@test.com',
            4
        );

        // 4. Tenant expired 2 days ago (in grace period)
        $this->createTenantWithBilling(
            'grace-period',
            'Grace Period Shop',
            $premiumPlan,
            now()->subDays(32), // Paid 32 days ago, expired 2 days ago
            'grace@test.com',
            5,
            true // expired
        );

        // 5. Tenant with multiple paid invoices (good payment history)
        $this->createTenantWithBilling(
            'good-history',
            'Good Payment History Shop',
            $premiumPlan,
            now()->subDays(15), // Paid 15 days ago, 15 days remaining
            'good-history@test.com',
            6
        );

        // 6. Tenant on Starter plan expiring soon
        $this->createTenantWithBilling(
            'starter-expires',
            'Starter Plan Shop',
            $starterPlan,
            now()->subDays(28), // Expires in 2 days
            'starter@test.com',
            2
        );

        $this->command->info('✓ Created billing test tenants:');
        $this->command->info('');
        $this->command->info('  1. expires-soon.localhost');
        $this->command->info('     - Email: expires-soon@test.com | Password: password');
        $this->command->info('     - Status: Active, expires in 3 days');
        $this->command->info('     - Plan: Premium (₱999/month)');
        $this->command->info('     - Invoices: 3 paid invoices');
        $this->command->info('');
        $this->command->info('  2. expires-tomorrow.localhost');
        $this->command->info('     - Email: expires-tomorrow@test.com | Password: password');
        $this->command->info('     - Status: Active, expires TOMORROW');
        $this->command->info('     - Plan: Premium (₱999/month)');
        $this->command->info('     - Invoices: 2 paid invoices');
        $this->command->info('');
        $this->command->info('  3. expires-today.localhost');
        $this->command->info('     - Email: expires-today@test.com | Password: password');
        $this->command->info('     - Status: Active, expires TODAY');
        $this->command->info('     - Plan: Premium (₱999/month)');
        $this->command->info('     - Invoices: 4 paid invoices');
        $this->command->info('');
        $this->command->info('  4. grace-period.localhost');
        $this->command->info('     - Email: grace@test.com | Password: password');
        $this->command->info('     - Status: EXPIRED 2 days ago, in grace period (5 days left)');
        $this->command->info('     - Plan: Premium (₱999/month)');
        $this->command->info('     - Invoices: 5 paid invoices + 1 OVERDUE');
        $this->command->info('');
        $this->command->info('  5. good-history.localhost');
        $this->command->info('     - Email: good-history@test.com | Password: password');
        $this->command->info('     - Status: Active, 15 days remaining');
        $this->command->info('     - Plan: Premium (₱999/month)');
        $this->command->info('     - Invoices: 6 paid invoices (good history)');
        $this->command->info('');
        $this->command->info('  6. starter-expires.localhost');
        $this->command->info('     - Email: starter@test.com | Password: password');
        $this->command->info('     - Status: Active, expires in 2 days');
        $this->command->info('     - Plan: Starter (Free)');
        $this->command->info('     - Invoices: 2 paid invoices');
        $this->command->info('');
        $this->command->info('Run: php artisan tenants:migrate');
    }

    private function createTenantWithBilling(
        string $subdomain,
        string $shopName,
        SubscriptionPlan $plan,
        $lastPaymentDate,
        string $ownerEmail,
        int $invoiceCount = 1,
        bool $isExpired = false
    ): void {
        // Calculate subscription expiration
        $subscriptionExpiresAt = $plan->billing_cycle === 'yearly'
            ? $lastPaymentDate->copy()->addYear()
            : $lastPaymentDate->copy()->addMonth();

        // Create tenant
        $tenant = Tenant::create([
            'id' => $subdomain,
            'subscription_plan_id' => $plan->id,
            'trial_ends_at' => now()->subDays(60), // Trial ended long ago
            'subscription_expires_at' => $subscriptionExpiresAt,
            'grace_period_days' => 7,
            'is_paid' => !$isExpired, // False if expired
            'is_enabled' => true,
            'theme' => 'indigo',
            'features' => $plan->features,
        ]);

        $tenant->domains()->create(['domain' => $subdomain . '.localhost']);

        // Create historical payments and invoices
        for ($i = $invoiceCount; $i >= 1; $i--) {
            $paymentDate = $i === 1 
                ? $lastPaymentDate 
                : $lastPaymentDate->copy()->subMonths($i);

            $periodStart = $paymentDate->copy();
            $periodEnd = $plan->billing_cycle === 'yearly'
                ? $paymentDate->copy()->addYear()
                : $paymentDate->copy()->addMonth();

            // Create payment
            $payment = Payment::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'payment_type' => 'subscription',
                'paymongo_checkout_id' => 'cs_test_' . uniqid(),
                'paymongo_payment_id' => 'pay_test_' . uniqid(),
                'amount' => $plan->price,
                'currency' => 'PHP',
                'status' => 'paid',
                'payment_method' => $i % 2 === 0 ? 'gcash' : 'card',
                'description' => $plan->name . ' Plan - ' . ucfirst($plan->billing_cycle) . ' Subscription',
                'customer_name' => $shopName . ' Owner',
                'customer_email' => $ownerEmail,
                'paid_at' => $paymentDate,
                'created_at' => $paymentDate,
                'updated_at' => $paymentDate,
            ]);

            // Create invoice
            Invoice::create([
                'invoice_number' => 'INV-' . $paymentDate->format('Ym') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'payment_id' => $payment->id,
                'billing_name' => $shopName . ' Owner',
                'billing_email' => $ownerEmail,
                'billing_address' => '123 Test Street, Manila, Philippines',
                'subtotal' => $plan->price,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => $plan->price,
                'currency' => 'PHP',
                'issue_date' => $paymentDate,
                'due_date' => $paymentDate,
                'paid_at' => $paymentDate,
                'status' => 'paid',
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'notes' => 'Thank you for your payment!',
                'created_at' => $paymentDate,
                'updated_at' => $paymentDate,
            ]);
        }

        // If expired, create an overdue invoice
        if ($isExpired) {
            $overdueDate = $subscriptionExpiresAt->copy();
            
            Invoice::create([
                'invoice_number' => 'INV-' . now()->format('Ym') . '-' . str_pad($invoiceCount + 1, 4, '0', STR_PAD_LEFT),
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'payment_id' => null,
                'billing_name' => $shopName . ' Owner',
                'billing_email' => $ownerEmail,
                'billing_address' => '123 Test Street, Manila, Philippines',
                'subtotal' => $plan->price,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => $plan->price,
                'currency' => 'PHP',
                'issue_date' => $overdueDate,
                'due_date' => $overdueDate,
                'paid_at' => null,
                'status' => 'overdue',
                'period_start' => $overdueDate,
                'period_end' => $plan->billing_cycle === 'yearly' 
                    ? $overdueDate->copy()->addYear() 
                    : $overdueDate->copy()->addMonth(),
                'notes' => 'Payment overdue. Please renew your subscription.',
            ]);
        }

        // Create owner user in tenant database
        tenancy()->initialize($tenant);
        
        User::create([
            'name' => $shopName . ' Owner',
            'email' => $ownerEmail,
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);

        tenancy()->end();
    }
}
