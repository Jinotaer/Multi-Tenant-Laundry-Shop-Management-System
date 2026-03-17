<?php

use App\Models\Admin;
use App\Models\Tenant;
use App\Models\TenantRegistration;
use App\Services\AdminLayoutSettingsService;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->admin = Admin::factory()->create();
});

it('allows admins to save their layout settings', function () {
    $payload = adminLayoutPayload();

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.settings.theme'))
        ->assertOk()
        ->assertSee('Admin Layout')
        ->assertSee('Save Layout')
        ->assertSee('tenant-choice-card', false);

    $this->actingAs($this->admin, 'admin')
        ->patch(route('admin.settings.theme.update'), $payload)
        ->assertRedirect();

    $this->admin->refresh();

    expect($this->admin->theme)->toBe('emerald');
    expect($this->admin->layout_settings)->toBe([
        'sidebar_position' => 'right',
        'topbar_behavior' => 'static',
        'topbar_style' => 'accent',
        'sidebar_style' => 'floating',
        'color_mode' => 'dark',
        'font_size' => 'lg',
        'border_radius' => 'xl',
        'logo_visibility' => false,
        'dashboard_widget_order' => [
            'pending_registrations',
            'recent_shops',
            'total_shops',
            'active_workspaces',
        ],
    ]);
});

it('renders resolved admin shell settings and dashboard widgets in saved order', function () {
    $layoutSettingsService = app(AdminLayoutSettingsService::class);

    $this->admin->theme = 'emerald';
    $this->admin->layout_settings = $layoutSettingsService->buildLayoutSettings(adminLayoutPayload());
    $this->admin->save();

    $alphaTenantId = 'alpha'.Str::lower(Str::random(8));
    $betaTenantId = 'beta'.Str::lower(Str::random(8));

    Tenant::create([
        'id' => $alphaTenantId,
        'is_enabled' => true,
        'is_paid' => true,
        'data' => ['shop_name' => 'Alpha Shop'],
    ]);

    Tenant::create([
        'id' => $betaTenantId,
        'is_enabled' => true,
        'is_paid' => false,
        'data' => ['shop_name' => 'Beta Shop'],
        'trial_ends_at' => now()->addDays(3),
    ]);

    TenantRegistration::create([
        'shop_name' => 'Pending Shop',
        'subdomain' => 'pending-shop',
        'owner_name' => 'Pending Owner',
        'owner_email' => 'pending@example.com',
        'owner_password' => 'password',
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.settings.theme'))
        ->assertOk()
        ->assertSee('data-theme="emerald"', false)
        ->assertSee('data-sidebar-position="right"', false)
        ->assertSee('data-topbar-behavior="static"', false)
        ->assertSee('data-topbar-style="accent"', false)
        ->assertSee('data-sidebar-style="floating"', false)
        ->assertSee('data-color-mode="dark"', false)
        ->assertSee('data-font-size="lg"', false)
        ->assertSee('data-border-radius="xl"', false)
        ->assertSee('data-logo-visibility="false"', false)
        ->assertSee('class="pt-4 pb-4"', false)
        ->assertSee('class="tenant-topbar tenant-topbar-accent px-4 py-4 sm:px-6"', false)
        ->assertSee('tenant-wordmark tenant-wordmark-sidebar', false)
        ->assertSee('tenant-wordmark tenant-wordmark-topbar', false)
        ->assertSee('tenant-nav-active', false)
        ->assertSee('rounded-3xl border border-gray-200 bg-white/95 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/95', false);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeInOrder([
            'Pending Registrations',
            'Recent Shops',
            'Total Shops',
            'Active Workspaces',
        ])
        ->assertSee($alphaTenantId)
        ->assertSee($betaTenantId);
});

it('rejects invalid admin layout values and widget orders', function () {
    $this->actingAs($this->admin, 'admin')
        ->from(route('admin.settings.theme'))
        ->patch(route('admin.settings.theme.update'), adminLayoutPayload([
            'sidebar_position' => 'upside',
            'dashboard_widget_order' => [
                'pending_registrations',
                'pending_registrations',
                'ghost_widget',
                'active_workspaces',
            ],
        ]))
        ->assertRedirect(route('admin.settings.theme'))
        ->assertSessionHasErrorsIn('adminLayoutSettings', [
            'sidebar_position',
            'dashboard_widget_order',
            'dashboard_widget_order.2',
        ]);
});

function adminLayoutPayload(array $overrides = []): array
{
    return array_merge([
        'theme' => 'emerald',
        'sidebar_position' => 'right',
        'topbar_behavior' => 'static',
        'topbar_style' => 'accent',
        'sidebar_style' => 'floating',
        'color_mode' => 'dark',
        'font_size' => 'lg',
        'border_radius' => 'xl',
        'logo_visibility' => '0',
        'dashboard_widget_order' => [
            'pending_registrations',
            'recent_shops',
            'total_shops',
            'active_workspaces',
        ],
    ], $overrides);
}
