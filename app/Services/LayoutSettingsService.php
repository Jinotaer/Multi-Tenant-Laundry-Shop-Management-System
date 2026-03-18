<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;

class LayoutSettingsService
{
    /**
     * Get the application defaults for layout settings.
     *
     * @return array<string, mixed>
     */
    public function defaults(?Tenant $tenant = null): array
    {
        $defaults = config('layout.defaults', []);
        $defaults['theme'] = $tenant?->theme ?? ($defaults['theme'] ?? config('themes.default'));
        $defaults['dashboard_widget_order'] = $this->normalizeWidgetOrder(
            (array) ($defaults['dashboard_widget_order'] ?? []),
            $this->defaultWidgetKeys(),
        );

        return $defaults;
    }

    /**
     * Get the configured layout option definitions.
     *
     * @return array<string, array<string, array<string, string>>>
     */
    public function options(): array
    {
        return config('layout.options', []);
    }

    /**
     * Get the workspace widget catalog.
     *
     * @return array<string, array<string, mixed>>
     */
    public function workspaceWidgets(): array
    {
        return config('layout.widgets', []);
    }

    /**
     * Get the widget catalog available to the given user.
     *
     * @return array<string, array<string, mixed>>
     */
    public function availableWidgetsFor(?Authenticatable $user): array
    {
        $role = $this->role($user);

        if (! in_array($role, ['owner', 'staff'], true)) {
            return [];
        }

        return array_filter(
            $this->workspaceWidgets(),
            fn (array $widget): bool => in_array($role, $widget['roles'] ?? [], true),
        );
    }

    /**
     * Determine if the current user can manage workspace defaults.
     */
    public function canManageWorkspaceDefaults(?Authenticatable $user): bool
    {
        return $this->role($user) === 'owner';
    }

    /**
     * Determine if the current user can manage personal preferences.
     */
    public function canManagePersonalPreferences(?Authenticatable $user): bool
    {
        return in_array($this->role($user), ['staff', 'customer'], true);
    }

    /**
     * Determine if the current user can customize dashboard widgets.
     */
    public function canCustomizeWidgetOrder(?Authenticatable $user): bool
    {
        return in_array($this->role($user), ['owner', 'staff'], true);
    }

    /**
     * Get the normalized tenant defaults.
     *
     * @return array<string, mixed>
     */
    public function tenantDefaults(?Tenant $tenant): array
    {
        $defaults = $this->defaults($tenant);

        if (! $tenant) {
            return $defaults;
        }

        $storedSettings = $this->normalizeSettings(
            (array) ($tenant->layout_settings ?? []),
            true,
            $this->defaultWidgetKeys(),
        );

        $tenantDefaults = array_merge($defaults, Arr::except($storedSettings, ['theme']));
        $tenantDefaults['theme'] = $tenant->theme ?? $defaults['theme'];
        $tenantDefaults['dashboard_widget_order'] = $this->normalizeWidgetOrder(
            (array) ($tenantDefaults['dashboard_widget_order'] ?? []),
            $this->defaultWidgetKeys(),
        );

        return $tenantDefaults;
    }

    /**
     * Get the normalized user overrides.
     *
     * @return array<string, mixed>
     */
    public function userOverrides(?Authenticatable $user): array
    {
        if (! $user || ! $this->canManagePersonalPreferences($user)) {
            return [];
        }

        $allowedWidgetKeys = array_keys($this->availableWidgetsFor($user));
        $overrides = $this->normalizeSettings(
            (array) data_get($user, 'layout_preferences', []),
            $this->canCustomizeWidgetOrder($user),
            $allowedWidgetKeys,
        );

        if (array_key_exists('dashboard_widget_order', $overrides)) {
            $overrides['dashboard_widget_order'] = $this->normalizeWidgetOrder(
                (array) $overrides['dashboard_widget_order'],
                $allowedWidgetKeys,
            );
        }

        return $overrides;
    }

    /**
     * Resolve the effective settings for a user within a tenant.
     *
     * @return array<string, mixed>
     */
    public function resolve(?Tenant $tenant, ?Authenticatable $user): array
    {
        $tenantDefaults = $this->tenantDefaults($tenant);
        $userOverrides = $this->userOverrides($user);
        $resolved = array_merge($tenantDefaults, Arr::except($userOverrides, ['dashboard_widget_order']));

        if ($this->canCustomizeWidgetOrder($user)) {
            $resolved['dashboard_widget_order'] = $this->normalizeWidgetOrder(
                (array) ($userOverrides['dashboard_widget_order'] ?? $tenantDefaults['dashboard_widget_order']),
                array_keys($this->availableWidgetsFor($user)),
            );
        } else {
            $resolved['dashboard_widget_order'] = [];
        }

        return $resolved;
    }

    /**
     * Get the resolved dashboard widget order for the current user.
     *
     * @return array<int, string>
     */
    public function dashboardWidgetsFor(?Tenant $tenant, ?Authenticatable $user): array
    {
        return $this->resolve($tenant, $user)['dashboard_widget_order'] ?? [];
    }

    /**
     * Build the JSON payload stored on the tenant model.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function buildTenantLayoutSettings(array $input): array
    {
        $settings = $this->normalizeSettings($input, true, $this->defaultWidgetKeys());
        $settings['dashboard_widget_order'] = $this->normalizeWidgetOrder(
            (array) ($settings['dashboard_widget_order'] ?? []),
            $this->defaultWidgetKeys(),
        );

        return Arr::except($settings, ['theme']);
    }

    /**
     * Build the user override payload relative to the tenant defaults.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function buildUserOverrides(?Tenant $tenant, ?Authenticatable $user, array $input): array
    {
        if (! $user || ! $this->canManagePersonalPreferences($user)) {
            return [];
        }

        $allowedWidgetKeys = array_keys($this->availableWidgetsFor($user));
        $tenantDefaults = $this->tenantDefaults($tenant);
        $normalized = $this->normalizeSettings(
            $input,
            $this->canCustomizeWidgetOrder($user),
            $allowedWidgetKeys,
        );
        $normalized['dashboard_widget_order'] = $this->canCustomizeWidgetOrder($user)
            ? $this->normalizeWidgetOrder(
                (array) ($normalized['dashboard_widget_order'] ?? $tenantDefaults['dashboard_widget_order']),
                $allowedWidgetKeys,
            )
            : [];

        $tenantWidgetOrder = $this->canCustomizeWidgetOrder($user)
            ? $this->normalizeWidgetOrder((array) $tenantDefaults['dashboard_widget_order'], $allowedWidgetKeys)
            : [];

        $overrides = [];

        foreach ($normalized as $key => $value) {
            if ($key === 'dashboard_widget_order') {
                if ($value !== $tenantWidgetOrder) {
                    $overrides[$key] = $value;
                }

                continue;
            }

            if (($tenantDefaults[$key] ?? null) !== $value) {
                $overrides[$key] = $value;
            }
        }

        return $overrides;
    }

    /**
     * Get the root font-size CSS value for a setting key.
     */
    public function fontSizeValue(string $fontSize): string
    {
        return config("layout.options.font_size.{$fontSize}.root_font_size", '16px');
    }

    /**
     * Get the CSS border radius value for a setting key.
     */
    public function borderRadiusValue(string $borderRadius): string
    {
        return config("layout.options.border_radius.{$borderRadius}.css_value", '1rem');
    }

    /**
     * Get the icon CSS size value for a setting key.
     */
    public function iconSizeValue(string $iconSize): string
    {
        return config("layout.options.icon_size.{$iconSize}.css_size", '1.25rem');
    }

    /**
     * Get the icon stroke width for a setting key.
     */
    public function iconStrokeValue(string $iconStroke): string
    {
        return config("layout.options.icon_stroke.{$iconStroke}.stroke_width", '1.5');
    }

    /**
     * Normalize an incoming layout settings payload.
     *
     * @param  array<string, mixed>  $settings
     * @param  array<int, string>  $allowedWidgetKeys
     * @return array<string, mixed>
     */
    private function normalizeSettings(array $settings, bool $allowWidgetOrder, array $allowedWidgetKeys): array
    {
        $normalized = [];

        foreach ([
            'sidebar_position',
            'topbar_behavior',
            'topbar_style',
            'sidebar_style',
            'color_mode',
            'font_size',
            'border_radius',
            'icon_size',
            'icon_stroke',
        ] as $setting) {
            $value = $settings[$setting] ?? null;

            if (is_string($value) && array_key_exists($value, config("layout.options.{$setting}", []))) {
                $normalized[$setting] = $value;
            }
        }

        $theme = $settings['theme'] ?? null;

        if (is_string($theme) && array_key_exists($theme, config('themes.presets', []))) {
            $normalized['theme'] = $theme;
        }

        if (array_key_exists('logo_visibility', $settings)) {
            $logoVisibility = filter_var(
                $settings['logo_visibility'],
                FILTER_VALIDATE_BOOL,
                FILTER_NULL_ON_FAILURE,
            );

            if ($logoVisibility !== null) {
                $normalized['logo_visibility'] = $logoVisibility;
            }
        }

        if ($allowWidgetOrder && array_key_exists('dashboard_widget_order', $settings) && is_array($settings['dashboard_widget_order'])) {
            $normalized['dashboard_widget_order'] = $this->normalizeWidgetOrder(
                $settings['dashboard_widget_order'],
                $allowedWidgetKeys,
            );
        }

        return $normalized;
    }

    /**
     * Normalize an ordered widget list and append any missing widgets.
     *
     * @param  array<int, mixed>  $widgetOrder
     * @param  array<int, string>  $allowedWidgetKeys
     * @return array<int, string>
     */
    private function normalizeWidgetOrder(array $widgetOrder, array $allowedWidgetKeys): array
    {
        $normalized = [];

        foreach ($widgetOrder as $widgetKey) {
            if (! is_string($widgetKey) || ! in_array($widgetKey, $allowedWidgetKeys, true) || in_array($widgetKey, $normalized, true)) {
                continue;
            }

            $normalized[] = $widgetKey;
        }

        foreach ($allowedWidgetKeys as $widgetKey) {
            if (! in_array($widgetKey, $normalized, true)) {
                $normalized[] = $widgetKey;
            }
        }

        return $normalized;
    }

    /**
     * Get the canonical dashboard widget keys.
     *
     * @return array<int, string>
     */
    private function defaultWidgetKeys(): array
    {
        return array_keys($this->workspaceWidgets());
    }

    /**
     * Resolve a tenant-authenticated user's role name.
     */
    private function role(?Authenticatable $user): string
    {
        if (! $user) {
            return 'guest';
        }

        if (method_exists($user, 'isOwner') && $user->isOwner()) {
            return 'owner';
        }

        if (method_exists($user, 'isStaff') && $user->isStaff()) {
            return 'staff';
        }

        return 'customer';
    }
}
