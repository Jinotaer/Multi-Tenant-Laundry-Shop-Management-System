<?php

use App\Models\Tenant;
use Illuminate\Support\Str;

beforeEach(function () {
    $tenantKey = 'auth'.Str::lower(Str::random(8));

    $this->tenantDomain = $tenantKey.'.localhost';

    $this->tenant = Tenant::create([
        'id' => $tenantKey,
        'is_enabled' => true,
        'is_paid' => true,
        'theme' => 'emerald',
        'layout_settings' => [
            'color_mode' => 'dark',
            'font_size' => 'lg',
            'border_radius' => 'xl',
        ],
        'data' => ['shop_name' => 'Theme Shop'],
    ]);

    $this->tenant->domains()->create([
        'domain' => $this->tenantDomain,
    ]);
});

afterEach(function () {
    tenancy()->end();

    if (isset($this->tenant) && $this->tenant->exists) {
        $this->tenant->delete();
    }
});

test('tenant login page uses the themed admin style auth card', function () {
    $this->get(tenantUrl($this->tenantDomain, '/login'))
        ->assertOk()
        ->assertSee('data-theme="emerald"', false)
        ->assertSee('data-color-mode="dark"', false)
        ->assertSee('data-font-size="lg"', false)
        ->assertSee('data-border-radius="xl"', false)
        ->assertSee('font-size: 17px; --tenant-radius: 1.5rem; --tenant-theme-accent: #10b981; --tenant-theme-accent-soft: #10b98118; --tenant-theme-accent-soft-strong: #10b98130;', false)
        ->assertSee('tenant-auth-shell', false)
        ->assertSee('tenant-auth-card', false)
        ->assertSee('tenant-auth-submit', false)
        ->assertSee('Sign in to LaundryTrack')
        ->assertSee('LaundryTrack')
        ->assertSee('Emerald')
        ->assertSee('Dark');
});

test('tenant register page reuses the same themed auth layout', function () {
    $this->get(tenantUrl($this->tenantDomain, '/register'))
        ->assertOk()
        ->assertSee('tenant-auth-shell', false)
        ->assertSee('tenant-auth-card', false)
        ->assertSee('tenant-auth-submit', false)
        ->assertSee('Create your LaundryTrack account')
        ->assertSee('Customer signup')
        ->assertSee('LaundryTrack')
        ->assertSee('Emerald')
        ->assertSee('Dark');
});

function tenantUrl(string $domain, string $path): string
{
    return "http://{$domain}{$path}";
}
