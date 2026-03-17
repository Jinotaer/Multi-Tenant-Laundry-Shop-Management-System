<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class ThemeService
{
    /**
     * Get the theme preset for the admin panel.
     *
     * @return array<string, string>
     */
    public function getAdminTheme(): array
    {
        $admin = Auth::guard('admin')->user();
        $key = $admin?->theme ?? config('themes.default');

        return $this->resolvePreset($key);
    }

    /**
     * Get the theme preset for the current tenant.
     *
     * @return array<string, string>
     */
    public function getTenantTheme(): array
    {
        $tenant = tenant();
        $tenantUser = Auth::guard('web')->user() ?? Auth::guard('customer')->user();
        $key = $tenant?->theme ?? config('themes.default');

        if ($tenantUser !== null && (! method_exists($tenantUser, 'isOwner') || ! $tenantUser->isOwner())) {
            $key = data_get($tenantUser, 'layout_preferences.theme') ?? $key;
        }

        return $this->resolvePreset($key);
    }

    /**
     * Get all available theme presets.
     *
     * @return array<string, array<string, string>>
     */
    public function getAllPresets(): array
    {
        return config('themes.presets', []);
    }

    /**
     * Get all available feature flag definitions.
     *
     * @return array<string, array<string, string>>
     */
    public function getAllFeatures(): array
    {
        return config('themes.features', []);
    }

    /**
     * Resolve a theme preset by key, falling back to default.
     *
     * @return array<string, string>
     */
    private function resolvePreset(string $key): array
    {
        $presets = config('themes.presets', []);
        $default = config('themes.default', 'indigo');

        return $presets[$key] ?? $presets[$default] ?? reset($presets);
    }
}
