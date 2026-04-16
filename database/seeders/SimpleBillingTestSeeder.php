<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantRegistration;
use App\Models\User;
use Illuminate\Database\Seeder;

class SimpleBillingTestSeeder extends Seeder
{
    /**
     * Create 2 simple test tenants:
     * 1. billing-test - For testing billing features (expires in 3 days)
     * 2. expired-test - For testing expired subscription (blocked access)
     */
    public function run(): void
    {
        $premiumPlan = SubscriptionPlan::where('name', 'Premium')->first();

        if (!$premiumPlan) {
            $this->command->error('Premium plan not found. Run DatabaseSeeder first.');
            return;
        }

        // 1. BILLING TEST TENANT - Active subscription expiring in 3 days
        $this->command->info('Creating billing-test tenant...');
        
        $billingTenant = Tenant::create([
            'id' => 'billing-test',
            'subscription_plan_id' => $premiumPlan->id,
            'trial_ends_at' => now()->subDays(60),
            'subscription_expires_at' => now()->addDays(3), // Expires in 3 days
            'grace_period_days' => 7,
            'is_paid' => true,
            'is_enabled' => true,
            'theme' => 'indigo',
            'features' => $premiumPlan->features,
        ]);

        $billingTenant->domains()->create(['domain' => 'billing-test.localhost']);

        // Create registration record so it appears in admin panel
        TenantRegistration::create([
            'subdomain' => 'billing-test',
            'shop_name' => 'Billing Test Shop',
            'owner_name' => 'Billing Test Owner',
            'owner_email' => 'billing@test.com',
            'owner_password' => bcrypt('password'),
            'status' => 'approved',
            'approved_at' => now()->subDays(60),
        ]);

        // Create 3 historical payments and invoices
        for ($i = 3; $i >= 1; $i--) {
            $paymentDate = now()->subMonths($i);
            $periodStart = $paymentDate->copy();
            $periodEnd = $paymentDate->copy()->addMonth();

            $payment = Payment::create([
                'tenant_id' => $billingTenant->id,
                'subscription_plan_id' => $premiumPlan->id,
                'payment_type' => 'subscription',
                'paymongo_checkout_id' => 'cs_test_' . uniqid(),
                'paymongo_payment_id' => 'pay_test_' . uniqid(),
                'amount' => $premiumPlan->price,
                'currency' => 'PHP',
                'status' => 'paid',
                'payment_method' => $i % 2 === 0 ? 'gcash' : 'card',
                'description' => 'Premium Plan - Monthly Subscription',
                'customer_name' => 'Billing Test Owner',
                'customer_email' => 'billing@test.com',
                'paid_at' => $paymentDate,
                'created_at' => $paymentDate,
                'updated_at' => $paymentDate,
            ]);

            Invoice::create([
                'invoice_number' => 'INV-' . $paymentDate->format('Ym') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'tenant_id' => $billingTenant->id,
                'subscription_plan_id' => $premiumPlan->id,
                'payment_id' => $payment->id,
                'billing_name' => 'Billing Test Owner',
                'billing_email' => 'billing@test.com',
                'billing_address' => '123 Test Street, Manila, Philippines',
                'subtotal' => $premiumPlan->price,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => $premiumPlan->price,
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

        // Create owner user
        tenancy()->initialize($billingTenant);
        User::create([
            'name' => 'Billing Test Owner',
            'email' => 'billing@test.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);
        tenancy()->end();

        // 2. EXPIRED TEST TENANT - Subscription expired, access blocked
        $this->command->info('Creating expired-test tenant...');
        
        $expiredTenant = Tenant::create([
            'id' => 'expired-test',
            'subscription_plan_id' => $premiumPlan->id,
            'trial_ends_at' => now()->subDays(60),
            'subscription_expires_at' => now()->subDays(10), // Expired 10 days ago
            'grace_period_days' => 7,
            'is_paid' => false,
            'is_enabled' => true,
            'theme' => 'indigo',
            'features' => $premiumPlan->features,
        ]);

        $expiredTenant->domains()->create(['domain' => 'expired-test.localhost']);

        // Create registration record so it appears in admin panel
        TenantRegistration::create([
            'subdomain' => 'expired-test',
            'shop_name' => 'Expired Test Shop',
            'owner_name' => 'Expired Test Owner',
            'owner_email' => 'expired@test.com',
            'owner_password' => bcrypt('password'),
            'status' => 'approved',
            'approved_at' => now()->subDays(60),
        ]);

        // Create 2 historical paid invoices
        for ($i = 2; $i >= 1; $i--) {
            $paymentDate = now()->subMonths($i + 1);
            $periodStart = $paymentDate->copy();
            $periodEnd = $paymentDate->copy()->addMonth();

            $payment = Payment::create([
                'tenant_id' => $expiredTenant->id,
                'subscription_plan_id' => $premiumPlan->id,
                'payment_type' => 'subscription',
                'paymongo_checkout_id' => 'cs_test_' . uniqid(),
                'paymongo_payment_id' => 'pay_test_' . uniqid(),
                'amount' => $premiumPlan->price,
                'currency' => 'PHP',
                'status' => 'paid',
                'payment_method' => 'card',
                'description' => 'Premium Plan - Monthly Subscription',
                'customer_name' => 'Expired Test Owner',
                'customer_email' => 'expired@test.com',
                'paid_at' => $paymentDate,
                'created_at' => $paymentDate,
                'updated_at' => $paymentDate,
            ]);

            Invoice::create([
                'invoice_number' => 'INV-' . $paymentDate->format('Ym') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'tenant_id' => $expiredTenant->id,
                'subscription_plan_id' => $premiumPlan->id,
                'payment_id' => $payment->id,
                'billing_name' => 'Expired Test Owner',
                'billing_email' => 'expired@test.com',
                'billing_address' => '456 Test Avenue, Manila, Philippines',
                'subtotal' => $premiumPlan->price,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => $premiumPlan->price,
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

        // Create 1 OVERDUE invoice
        $overdueDate = now()->subDays(10);
        Invoice::create([
            'invoice_number' => 'INV-' . now()->format('Ym') . '-0003',
            'tenant_id' => $expiredTenant->id,
            'subscription_plan_id' => $premiumPlan->id,
            'payment_id' => null,
            'billing_name' => 'Expired Test Owner',
            'billing_email' => 'expired@test.com',
            'billing_address' => '456 Test Avenue, Manila, Philippines',
            'subtotal' => $premiumPlan->price,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => $premiumPlan->price,
            'currency' => 'PHP',
            'issue_date' => $overdueDate,
            'due_date' => $overdueDate,
            'paid_at' => null,
            'status' => 'overdue',
            'period_start' => $overdueDate,
            'period_end' => $overdueDate->copy()->addMonth(),
            'notes' => 'Payment overdue. Please renew your subscription.',
        ]);

        // Create owner user
        tenancy()->initialize($expiredTenant);
        User::create([
            'name' => 'Expired Test Owner',
            'email' => 'expired@test.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);
        tenancy()->end();

        // Display summary
        $this->command->info('');
        $this->command->info('✅ Test tenants created successfully!');
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('🟢 BILLING TEST TENANT (Active - For Billing Features)');
        $this->command->info('   URL:      http://billing-test.localhost/login');
        $this->command->info('   Email:    billing@test.com');
        $this->command->info('   Password: password');
        $this->command->info('   Status:   ✅ Active (expires in 3 days)');
        $this->command->info('   Plan:     Premium (₱999/month)');
        $this->command->info('   Invoices: 3 paid invoices');
        $this->command->info('');
        $this->command->info('   Test:');
        $this->command->info('   • View billing dashboard at /billing');
        $this->command->info('   • View invoice details');
        $this->command->info('   • Download invoices');
        $this->command->info('   • See expiring soon warning');
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('🔴 EXPIRED TEST TENANT (Blocked - For Expiration Testing)');
        $this->command->info('   URL:      http://expired-test.localhost/login');
        $this->command->info('   Email:    expired@test.com');
        $this->command->info('   Password: password');
        $this->command->info('   Status:   ❌ Expired 10 days ago (BLOCKED)');
        $this->command->info('   Plan:     Premium (₱999/month)');
        $this->command->info('   Invoices: 2 paid + 1 OVERDUE');
        $this->command->info('');
        $this->command->info('   Test:');
        $this->command->info('   • Login redirects to /trial-expired page');
        $this->command->info('   • No sidebar or navigation');
        $this->command->info('   • Only "Renew Subscription" button visible');
        $this->command->info('   • Cannot access dashboard, orders, customers');
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('📝 Next Steps:');
        $this->command->info('   1. Run: php artisan tenants:migrate');
        $this->command->info('   2. Visit the URLs above and login');
        $this->command->info('   3. Test billing and expiration features');
        $this->command->info('');
    }
}
