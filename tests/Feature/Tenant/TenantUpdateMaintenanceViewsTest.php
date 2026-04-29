<?php

use App\Models\AppRelease;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->withoutMiddleware(VerifyCsrfToken::class);

    $tenantKey = 'tenantupdates'.Str::lower(Str::random(8));

    $this->tenantDomain = $tenantKey.'.localhost';
    $this->tenant = Tenant::create([
        'id' => $tenantKey,
        'is_enabled' => true,
        'is_paid' => true,
        'data' => ['shop_name' => 'Maintenance Views Shop'],
    ]);

    $this->tenant->domains()->create(['domain' => $this->tenantDomain]);

    $this->tenant->run(function (): void {
        Permission::ensureDefaultsExist();

        User::create([
            'name' => 'Owner User',
            'email' => 'owner@tenant-update-views.test',
            'password' => 'password',
            'role' => 'owner',
        ]);
    });

    $this->tenantUrl = fn (string $path): string => "http://{$this->tenantDomain}{$path}";
});

afterEach(function (): void {
    tenancy()->end();

    if (isset($this->tenant) && $this->tenant->exists) {
        $store = (string) config('updates.tenant_maintenance.cache_store', config('cache.default'));
        Cache::store($store)->forget('tenant:update:maintenance:'.$this->tenant->id);
        $this->tenant->delete();
    }
});

function seedActiveTenantUpdate(Tenant $tenant): AppRelease
{
    $currentRelease = AppRelease::create([
        'version_tag' => 'v1.4.5',
        'name' => 'Current release',
        'body' => 'Current version.',
        'published_at' => now()->subDay(),
    ]);

    $targetRelease = AppRelease::create([
        'version_tag' => 'v1.4.6',
        'name' => 'Target release',
        'body' => 'Standalone update flow fix.',
        'published_at' => now(),
    ]);

    $tenant->updates()->create([
        'app_release_id' => $currentRelease->id,
        'status' => 'updated',
        'is_current' => true,
        'action_taken_at' => now()->subDays(2),
    ]);

    $tenant->updates()->create([
        'app_release_id' => $targetRelease->id,
        'status' => 'applying',
        'is_current' => false,
        'action_taken_at' => now(),
    ]);

    $store = (string) config('updates.tenant_maintenance.cache_store', config('cache.default'));
    Cache::store($store)->put(
        'tenant:update:maintenance:'.$tenant->id,
        ['version' => $targetRelease->version_tag],
        now()->addMinutes(30)
    );

    return $targetRelease;
}

function authenticateTenantOwner($testCase): void
{
    tenancy()->initialize($testCase->tenant);

    $user = User::where('email', 'owner@tenant-update-views.test')->firstOrFail();

    $testCase->actingAs($user);

    tenancy()->end();
}

test('update center switches to standalone in progress page during active tenant update', function (): void {
    $targetRelease = seedActiveTenantUpdate($this->tenant);

    authenticateTenantOwner($this);

    $response = $this->get(($this->tenantUrl)('/updates'))
        ->assertOk()
        ->assertSee($targetRelease->version_tag)
        ->assertSee('View Live Status')
        ->assertSee('files and frontend assets are being replaced')
        ->assertDontSee('Check for Updates');

    expect((string) $response->headers->get('Cache-Control'))
        ->toContain('no-store')
        ->toContain('no-cache')
        ->toContain('must-revalidate')
        ->toContain('max-age=0');
});

test('update status route uses standalone runner page during active tenant update', function (): void {
    $targetRelease = seedActiveTenantUpdate($this->tenant);

    authenticateTenantOwner($this);

    $response = $this->get(($this->tenantUrl)("/updates/{$targetRelease->id}/status"))
        ->assertOk()
        ->assertSee($targetRelease->version_tag)
        ->assertSee('Back to Update Center')
        ->assertSee('application assets are changing')
        ->assertDontSee('Check for Updates');

    expect((string) $response->headers->get('Cache-Control'))
        ->toContain('no-store')
        ->toContain('no-cache')
        ->toContain('must-revalidate')
        ->toContain('max-age=0');
});

test('non update routes show maintenance page with update center escape hatch during active tenant update', function (): void {
    seedActiveTenantUpdate($this->tenant);

    authenticateTenantOwner($this);

    $this->get(($this->tenantUrl)('/dashboard'))
        ->assertStatus(503)
        ->assertSee('System Under Maintenance')
        ->assertSee('Open Update Center')
        ->assertSee('Update in progress');
});
