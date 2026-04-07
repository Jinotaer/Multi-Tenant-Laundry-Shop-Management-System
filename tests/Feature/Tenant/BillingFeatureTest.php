<?php

use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $tenantKey = 'billing'.Str::lower(Str::random(8));

    $this->plan = SubscriptionPlan::factory()->premium()->create([
        'billing_cycle' => 'monthly',
    ]);

    $this->tenantDomain = $tenantKey.'.localhost';
    $this->tenant = Tenant::create([
        'id' => $tenantKey,
        'subscription_plan_id' => $this->plan->id,
        'features' => $this->plan->features,
        'is_enabled' => true,
        'is_paid' => true,
        'data' => ['shop_name' => 'Billing Shop'],
    ]);

    $this->tenant->domains()->create([
        'domain' => $this->tenantDomain,
    ]);

    $this->tenant->run(function (): void {
        User::create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);
    });

    $this->tenantUrl = fn (string $path): string => "http://{$this->tenantDomain}{$path}";
    $this->loginOwner = function (): void {
        $this->post(($this->tenantUrl)('/login'), [
            'email' => 'owner@example.com',
            'password' => 'password',
        ])->assertRedirect(route('tenant.dashboard', absolute: false));
    };
});

afterEach(function () {
    tenancy()->end();

    if (isset($this->tenant) && $this->tenant->exists) {
        $this->tenant->delete();
    }
});

test('owner can view tenant billing index with own invoices', function () {
    $ownInvoice = Invoice::create([
        'invoice_number' => 'INV-TEST-0001',
        'tenant_id' => $this->tenant->id,
        'subscription_plan_id' => $this->plan->id,
        'billing_name' => 'Billing Owner',
        'billing_email' => 'billing-owner@example.com',
        'subtotal' => 999,
        'tax_rate' => 0,
        'tax_amount' => 0,
        'total' => 999,
        'currency' => 'PHP',
        'issue_date' => now()->toDateString(),
        'due_date' => now()->toDateString(),
        'status' => Invoice::STATUS_PAID,
    ]);

    $otherTenant = Tenant::create([
        'id' => 'other-'.Str::lower(Str::random(6)),
        'subscription_plan_id' => $this->plan->id,
        'features' => $this->plan->features,
        'is_enabled' => true,
        'is_paid' => true,
        'data' => ['shop_name' => 'Other Shop'],
    ]);

    Invoice::create([
        'invoice_number' => 'INV-TEST-0002',
        'tenant_id' => $otherTenant->id,
        'subscription_plan_id' => $this->plan->id,
        'billing_name' => 'Other Owner',
        'billing_email' => 'other-owner@example.com',
        'subtotal' => 799,
        'tax_rate' => 0,
        'tax_amount' => 0,
        'total' => 799,
        'currency' => 'PHP',
        'issue_date' => now()->toDateString(),
        'due_date' => now()->toDateString(),
        'status' => Invoice::STATUS_PAID,
    ]);

    ($this->loginOwner)();

    $this->get(($this->tenantUrl)('/billing'))
        ->assertOk()
        ->assertSee('Billing & Invoices')
        ->assertSee($ownInvoice->invoice_number)
        ->assertDontSee('INV-TEST-0002');

    $otherTenant->delete();
});

test('owner can view own invoice details', function () {
    $invoice = Invoice::create([
        'invoice_number' => 'INV-TEST-1001',
        'tenant_id' => $this->tenant->id,
        'subscription_plan_id' => $this->plan->id,
        'billing_name' => 'Billing Owner',
        'billing_email' => 'billing-owner@example.com',
        'subtotal' => 1299,
        'tax_rate' => 0,
        'tax_amount' => 0,
        'total' => 1299,
        'currency' => 'PHP',
        'issue_date' => now()->toDateString(),
        'due_date' => now()->toDateString(),
        'status' => Invoice::STATUS_PAID,
    ]);

    ($this->loginOwner)();

    $this->get(($this->tenantUrl)("/billing/{$invoice->id}"))
        ->assertOk()
        ->assertSee($invoice->invoice_number)
        ->assertSee('Billing Owner')
        ->assertSee('INVOICE');
});

test('owner cannot view another tenant invoice details', function () {
    $otherTenant = Tenant::create([
        'id' => 'outside-'.Str::lower(Str::random(6)),
        'subscription_plan_id' => $this->plan->id,
        'features' => $this->plan->features,
        'is_enabled' => true,
        'is_paid' => true,
        'data' => ['shop_name' => 'Outside Shop'],
    ]);

    $foreignInvoice = Invoice::create([
        'invoice_number' => 'INV-TEST-2002',
        'tenant_id' => $otherTenant->id,
        'subscription_plan_id' => $this->plan->id,
        'billing_name' => 'Outside Owner',
        'billing_email' => 'outside-owner@example.com',
        'subtotal' => 1399,
        'tax_rate' => 0,
        'tax_amount' => 0,
        'total' => 1399,
        'currency' => 'PHP',
        'issue_date' => now()->toDateString(),
        'due_date' => now()->toDateString(),
        'status' => Invoice::STATUS_PAID,
    ]);

    ($this->loginOwner)();

    $this->get(($this->tenantUrl)("/billing/{$foreignInvoice->id}"))
        ->assertForbidden();

    $otherTenant->delete();
});

test('owner can download own invoice html file', function () {
    $invoice = Invoice::create([
        'invoice_number' => 'INV-TEST-3003',
        'tenant_id' => $this->tenant->id,
        'subscription_plan_id' => $this->plan->id,
        'billing_name' => 'Billing Owner',
        'billing_email' => 'billing-owner@example.com',
        'subtotal' => 1599,
        'tax_rate' => 0,
        'tax_amount' => 0,
        'total' => 1599,
        'currency' => 'PHP',
        'issue_date' => now()->toDateString(),
        'due_date' => now()->toDateString(),
        'status' => Invoice::STATUS_PAID,
    ]);

    ($this->loginOwner)();

    $this->get(($this->tenantUrl)("/billing/{$invoice->id}/download"))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="INV-TEST-3003.html"')
        ->assertSee($invoice->invoice_number);
});

test('owner can still access billing during grace period', function () {
    $this->tenant->update([
        'is_paid' => false,
        'trial_ends_at' => null,
        'subscription_expires_at' => now()->subDay(),
        'grace_period_days' => 7,
    ]);

    ($this->loginOwner)();

    $this->get(($this->tenantUrl)('/billing'))
        ->assertOk()
        ->assertSee('Billing & Invoices');
});

test('owner is redirected away from billing when grace period has ended', function () {
    $this->tenant->update([
        'is_paid' => false,
        'trial_ends_at' => null,
        'subscription_expires_at' => now()->subDays(10),
        'grace_period_days' => 7,
    ]);

    ($this->loginOwner)();

    $this->get(($this->tenantUrl)('/billing'))
        ->assertRedirect(route('tenant.trial-expired', absolute: false));
});
