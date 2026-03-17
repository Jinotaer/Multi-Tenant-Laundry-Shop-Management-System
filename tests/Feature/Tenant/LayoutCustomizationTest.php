<?php

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LayoutSettingsService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

beforeEach(function () {
    $tenantKey = 'layout'.Str::lower(Str::random(8));

    $this->tenantDomain = $tenantKey.'.localhost';

    $this->tenant = Tenant::create([
        'id' => $tenantKey,
        'is_enabled' => true,
        'is_paid' => true,
        'theme' => 'indigo',
        'features' => ['customer_portal', 'expense_tracking', 'reports'],
        'data' => ['shop_name' => 'Layout Shop'],
    ]);

    $this->tenant->domains()->create([
        'domain' => $this->tenantDomain,
    ]);

    $this->owner = $this->tenant->run(function (): User {
        return User::create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);
    });
});

afterEach(function () {
    tenancy()->end();

    if (isset($this->tenant) && $this->tenant->exists) {
        $this->tenant->delete();
    }
});

test('owner can view the layout page and save workspace defaults', function () {
    $this->post(tenantUrl($this->tenantDomain, '/login'), [
        'email' => 'owner@example.com',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(tenantUrl($this->tenantDomain, '/settings/theme'))
        ->assertOk()
        ->assertSee('Workspace Defaults')
        ->assertDontSee('My Preferences')
        ->assertSee('Selected')
        ->assertSee('tenant-choice-card', false)
        ->assertSee('tenant-selection-check', false)
        ->assertDontSee(":style=\"'--selection-accent: ' + (themeColors[selectedTheme] || '#6366f1')\"", false)
        ->assertSee('--selection-accent: #6366f1', false)
        ->assertSee('--selection-accent: #3b82f6', false);

    $payload = workspaceDefaultsPayload();

    $this->patch(tenantUrl($this->tenantDomain, '/settings/theme'), $payload)
        ->assertRedirect(tenantUrl($this->tenantDomain, '/settings/theme'));

    $this->tenant->refresh();

    expect($this->tenant->theme)->toBe('emerald');
    expect($this->tenant->layout_settings)->toBe([
        'sidebar_position' => 'right',
        'topbar_behavior' => 'static',
        'topbar_style' => 'accent',
        'sidebar_style' => 'floating',
        'color_mode' => 'light',
        'font_size' => 'lg',
        'border_radius' => 'xl',
        'logo_visibility' => false,
        'dashboard_widget_order' => [
            'recent_orders',
            'enabled_features',
            'owner_metrics',
            'welcome',
            'overview_stats',
        ],
    ]);
});

test('profile settings render dark ready form controls', function () {
    $this->post(tenantUrl($this->tenantDomain, '/login'), [
        'email' => 'owner@example.com',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(tenantUrl($this->tenantDomain, '/settings/profile'))
        ->assertOk()
        ->assertSee('dark:text-slate-300', false)
        ->assertSee('dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-400', false)
        ->assertSee('dark:focus:ring-offset-slate-950', false)
        ->assertSee('dark:border dark:border-slate-800 dark:bg-slate-900', false);
});

test('owners cannot manage personal preferences and stale owner overrides are ignored', function () {
    $layoutSettingsService = app(LayoutSettingsService::class);

    $this->tenant->theme = 'emerald';
    $this->tenant->layout_settings = $layoutSettingsService->buildTenantLayoutSettings(workspaceDefaultsPayload());
    $this->tenant->save();

    $this->tenant->run(function (): void {
        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $owner->layout_preferences = personalPreferencesPayload();
        $owner->save();
    });

    $this->post(tenantUrl($this->tenantDomain, '/login'), [
        'email' => 'owner@example.com',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(tenantUrl($this->tenantDomain, '/settings/theme'))
        ->assertOk()
        ->assertSee('Workspace Defaults')
        ->assertDontSee('My Preferences')
        ->assertSee('data-theme="emerald"', false)
        ->assertSee('data-sidebar-style="floating"', false);

    $this->patch(tenantUrl($this->tenantDomain, '/settings/theme/preferences'), personalPreferencesPayload())
        ->assertForbidden();

    $this->from(tenantUrl($this->tenantDomain, '/settings/theme'))
        ->delete(tenantUrl($this->tenantDomain, '/settings/theme/preferences'))
        ->assertForbidden();
});

test('staff can save personal preferences but cannot update workspace defaults', function () {
    $staff = $this->tenant->run(function (): User {
        return User::create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'password' => 'password',
            'role' => 'staff',
        ]);
    });

    $this->post(tenantUrl($this->tenantDomain, '/login'), [
        'email' => 'staff@example.com',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(tenantUrl($this->tenantDomain, '/settings/theme'))
        ->assertOk()
        ->assertDontSee('Save Workspace Defaults')
        ->assertSee('My Preferences')
        ->assertSee('My Dashboard Widget Order');

    $payload = personalPreferencesPayload();

    $this->patch(tenantUrl($this->tenantDomain, '/settings/theme/preferences'), $payload)
        ->assertRedirect(tenantUrl($this->tenantDomain, '/settings/theme'));

    $storedPreferences = $this->tenant->run(fn (): ?array => User::find($staff->id)?->layout_preferences);

    expect($storedPreferences['theme'] ?? null)->toBe('rose');
    expect($storedPreferences['sidebar_position'] ?? null)->toBe('right');
    expect($storedPreferences['topbar_behavior'] ?? null)->toBe('static');
    expect($storedPreferences['topbar_style'] ?? null)->toBe('card');
    expect($storedPreferences['sidebar_style'] ?? null)->toBe('compact');
    expect($storedPreferences['color_mode'] ?? null)->toBe('dark');
    expect($storedPreferences['font_size'] ?? null)->toBe('sm');
    expect($storedPreferences['border_radius'] ?? null)->toBe('md');
    expect($storedPreferences['logo_visibility'] ?? null)->toBeFalse();
    expect($storedPreferences['dashboard_widget_order'] ?? null)->toBe([
        'recent_orders',
        'welcome',
        'overview_stats',
    ]);

    $this->patch(tenantUrl($this->tenantDomain, '/settings/theme'), workspaceDefaultsPayload())
        ->assertForbidden();
});

test('customers can save shell preferences but cannot update workspace defaults', function () {
    $customer = $this->tenant->run(function (): Customer {
        return Customer::create([
            'name' => 'Portal Customer',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);
    });

    $this->post(tenantUrl($this->tenantDomain, '/login'), [
        'email' => 'customer@example.com',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(tenantUrl($this->tenantDomain, '/settings/theme'))
        ->assertOk()
        ->assertDontSee('Save Workspace Defaults')
        ->assertSee('My Preferences')
        ->assertDontSee('My Dashboard Widget Order')
        ->assertSee('Personal widget ordering is available for staff dashboards.');

    $payload = personalPreferencesPayload();
    unset($payload['dashboard_widget_order']);

    $this->patch(tenantUrl($this->tenantDomain, '/settings/theme/preferences'), $payload)
        ->assertRedirect(tenantUrl($this->tenantDomain, '/settings/theme'));

    $storedPreferences = $this->tenant->run(fn (): ?array => Customer::find($customer->id)?->layout_preferences);

    expect($storedPreferences['theme'] ?? null)->toBe('rose');
    expect($storedPreferences['sidebar_position'] ?? null)->toBe('right');
    expect($storedPreferences['topbar_behavior'] ?? null)->toBe('static');
    expect($storedPreferences['topbar_style'] ?? null)->toBe('card');
    expect($storedPreferences['sidebar_style'] ?? null)->toBe('compact');
    expect($storedPreferences['color_mode'] ?? null)->toBe('dark');
    expect($storedPreferences['font_size'] ?? null)->toBe('sm');
    expect($storedPreferences['border_radius'] ?? null)->toBe('md');
    expect($storedPreferences['logo_visibility'] ?? null)->toBeFalse();

    $this->patch(tenantUrl($this->tenantDomain, '/settings/theme'), workspaceDefaultsPayload())
        ->assertForbidden();
});

test('resetting personal preferences clears overrides and falls back to tenant defaults', function () {
    $layoutSettingsService = app(LayoutSettingsService::class);

    $this->tenant->theme = 'emerald';
    $this->tenant->layout_settings = $layoutSettingsService->buildTenantLayoutSettings(workspaceDefaultsPayload());
    $this->tenant->save();

    $staff = $this->tenant->run(function (): User {
        return User::create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'password' => 'password',
            'role' => 'staff',
            'layout_preferences' => personalPreferencesPayload(),
        ]);
    });

    $this->post(tenantUrl($this->tenantDomain, '/login'), [
        'email' => 'staff@example.com',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->from(tenantUrl($this->tenantDomain, '/settings/theme'))
        ->delete(tenantUrl($this->tenantDomain, '/settings/theme/preferences'))
        ->assertRedirect(tenantUrl($this->tenantDomain, '/settings/theme'));

    $storedPreferences = $this->tenant->run(fn (): ?array => User::find($staff->id)?->layout_preferences);

    expect($storedPreferences)->toBeNull();

    $this->get(tenantUrl($this->tenantDomain, '/settings/theme'))
        ->assertOk()
        ->assertSee('data-theme="emerald"', false)
        ->assertSee('data-sidebar-position="right"', false)
        ->assertSee('data-topbar-behavior="static"', false)
        ->assertSee('data-topbar-style="accent"', false)
        ->assertSee('data-sidebar-style="floating"', false)
        ->assertSee('data-logo-visibility="false"', false)
        ->assertSee('Default');
});

test('resolved layout markers render workspace defaults for owners and user overrides for staff', function () {
    $layoutSettingsService = app(LayoutSettingsService::class);

    $this->tenant->theme = 'emerald';
    $this->tenant->layout_settings = $layoutSettingsService->buildTenantLayoutSettings(workspaceDefaultsPayload());
    $this->tenant->save();

    $this->tenant->run(function (): void {
        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $owner->layout_preferences = personalPreferencesPayload();
        $owner->save();

        User::create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'password' => 'password',
            'role' => 'staff',
            'layout_preferences' => personalPreferencesPayload(),
        ]);
    });

    $this->post(tenantUrl($this->tenantDomain, '/login'), [
        'email' => 'owner@example.com',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(tenantUrl($this->tenantDomain, '/settings/theme'))
        ->assertOk()
        ->assertSee('data-theme="emerald"', false)
        ->assertSee('data-sidebar-position="right"', false)
        ->assertSee('data-topbar-behavior="static"', false)
        ->assertSee('data-topbar-style="accent"', false)
        ->assertSee('data-sidebar-style="floating"', false)
        ->assertSee('data-color-mode="light"', false)
        ->assertSee('data-font-size="lg"', false)
        ->assertSee('data-border-radius="xl"', false)
        ->assertSee('data-logo-visibility="false"', false)
        ->assertSee('class="pt-4 pb-4"', false)
        ->assertSee('class="tenant-topbar tenant-topbar-accent px-4 py-4 sm:px-6"', false)
        ->assertSee('tenant-wordmark tenant-wordmark-sidebar', false)
        ->assertSee('tenant-wordmark tenant-wordmark-topbar', false)
        ->assertSee('tenant-wordmark-accent', false)
        ->assertSee('flex min-h-[5.5rem] items-center justify-between border-b border-gray-200 px-6 pt-5 pb-6 dark:border-slate-800', false)
        ->assertSee('rounded-3xl border border-gray-200 bg-white/95 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/95', false)
        ->assertSee('font-size: 17px; --tenant-radius: 1.5rem; --tenant-theme-accent: #10b981; --tenant-theme-accent-soft: #10b98118; --tenant-theme-accent-soft-strong: #10b98130;', false)
        ->assertSee('tenant-nav-active', false);

    $this->post(tenantUrl($this->tenantDomain, '/logout'))
        ->assertRedirect(route('tenant.login', absolute: false));

    $this->post(tenantUrl($this->tenantDomain, '/login'), [
        'email' => 'staff@example.com',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(tenantUrl($this->tenantDomain, '/settings/theme'))
        ->assertOk()
        ->assertSee('data-theme="rose"', false)
        ->assertSee('data-sidebar-position="right"', false)
        ->assertSee('data-topbar-behavior="static"', false)
        ->assertSee('data-topbar-style="card"', false)
        ->assertSee('data-sidebar-style="compact"', false)
        ->assertSee('data-color-mode="dark"', false)
        ->assertSee('data-font-size="sm"', false)
        ->assertSee('data-border-radius="md"', false)
        ->assertSee('data-logo-visibility="false"', false)
        ->assertSee('class="pt-4 pb-4"', false)
        ->assertSee('class="tenant-topbar tenant-topbar-card px-4 py-4 sm:px-6"', false)
        ->assertSee('font-size: 15px; --tenant-radius: 0.75rem; --tenant-theme-accent: #f43f5e; --tenant-theme-accent-soft: #f43f5e18; --tenant-theme-accent-soft-strong: #f43f5e30;', false)
        ->assertDontSee(":style=\"'--selection-accent: ' + (themeColors[selectedTheme] || '#6366f1')\"", false)
        ->assertSee('--selection-accent: #f43f5e', false)
        ->assertSee('tenant-nav-active', false);
});

test('validation rejects invalid values duplicate and unknown widget ids and role ineligible widget orders', function () {
    $this->post(tenantUrl($this->tenantDomain, '/login'), [
        'email' => 'owner@example.com',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->from(tenantUrl($this->tenantDomain, '/settings/theme'))
        ->patch(tenantUrl($this->tenantDomain, '/settings/theme'), workspaceDefaultsPayload([
            'sidebar_position' => 'upside',
            'dashboard_widget_order' => [
                'welcome',
                'welcome',
                'owner_metrics',
                'recent_orders',
                'ghost_widget',
            ],
        ]))
        ->assertRedirect(tenantUrl($this->tenantDomain, '/settings/theme'))
        ->assertSessionHasErrorsIn('tenantLayoutDefaults', [
            'sidebar_position',
            'dashboard_widget_order',
            'dashboard_widget_order.4',
        ]);

    $this->post(tenantUrl($this->tenantDomain, '/logout'))
        ->assertRedirect(route('tenant.login', absolute: false));

    $this->tenant->run(function (): void {
        Customer::create([
            'name' => 'Portal Customer',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);
    });

    $this->post(tenantUrl($this->tenantDomain, '/login'), [
        'email' => 'customer@example.com',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->from(tenantUrl($this->tenantDomain, '/settings/theme'))
        ->patch(tenantUrl($this->tenantDomain, '/settings/theme/preferences'), personalPreferencesPayload([
            'dashboard_widget_order' => ['welcome'],
        ]))
        ->assertRedirect(tenantUrl($this->tenantDomain, '/settings/theme'))
        ->assertSessionHasErrorsIn('userLayoutPreferences', ['dashboard_widget_order']);
});

function tenantUrl(string $domain, string $path): string
{
    return "http://{$domain}{$path}";
}

function workspaceDefaultsPayload(array $overrides = []): array
{
    return array_merge([
        'theme' => 'emerald',
        'sidebar_position' => 'right',
        'topbar_behavior' => 'static',
        'topbar_style' => 'accent',
        'sidebar_style' => 'floating',
        'color_mode' => 'light',
        'font_size' => 'lg',
        'border_radius' => 'xl',
        'logo_visibility' => '0',
        'dashboard_widget_order' => [
            'recent_orders',
            'enabled_features',
            'owner_metrics',
            'welcome',
            'overview_stats',
        ],
    ], $overrides);
}

function personalPreferencesPayload(array $overrides = []): array
{
    return array_merge([
        'theme' => 'rose',
        'sidebar_position' => 'right',
        'topbar_behavior' => 'static',
        'topbar_style' => 'card',
        'sidebar_style' => 'compact',
        'color_mode' => 'dark',
        'font_size' => 'sm',
        'border_radius' => 'md',
        'logo_visibility' => '0',
        'dashboard_widget_order' => [
            'recent_orders',
            'welcome',
            'overview_stats',
        ],
    ], $overrides);
}
