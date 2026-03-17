<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Support\Arr;

class AdminLayoutSettingsService
{
    /**
     * Get the application defaults for the admin layout.
     *
     * @return array<string, mixed>
     */
    public function defaults(?Admin $admin = null): array
    {
        $defaults = config('admin-layout.defaults', []);
        $defaults['theme'] = $admin?->theme ?? ($defaults['theme'] ?? config('themes.default'));
        $defaults['dashboard_widget_order'] = $this->normalizeWidgetOrder(
            (array) ($defaults['dashboard_widget_order'] ?? []),
            $this->widgetKeys(),
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
     * Get the admin widget catalog.
     *
     * @return array<string, array<string, string>>
     */
    public function widgets(): array
    {
        return config('admin-layout.widgets', []);
    }

    /**
     * Resolve the effective layout for the given admin.
     *
     * @return array<string, mixed>
     */
    public function resolve(?Admin $admin): array
    {
        $defaults = $this->defaults($admin);

        if (! $admin) {
            return $defaults;
        }

        $storedSettings = $this->normalizeSettings((array) ($admin->layout_settings ?? []));
        $resolved = array_merge($defaults, Arr::except($storedSettings, ['theme']));
        $resolved['theme'] = $admin->theme ?? $defaults['theme'];
        $resolved['dashboard_widget_order'] = $this->normalizeWidgetOrder(
            (array) ($resolved['dashboard_widget_order'] ?? []),
            $this->widgetKeys(),
        );

        return $resolved;
    }

    /**
     * Get the resolved admin dashboard widget order.
     *
     * @return array<int, string>
     */
    public function dashboardWidgetsFor(?Admin $admin): array
    {
        return $this->resolve($admin)['dashboard_widget_order'] ?? [];
    }

    /**
     * Build the JSON payload stored on the admin model.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function buildLayoutSettings(array $input): array
    {
        $settings = $this->normalizeSettings($input);
        $settings['dashboard_widget_order'] = $this->normalizeWidgetOrder(
            (array) ($settings['dashboard_widget_order'] ?? []),
            $this->widgetKeys(),
        );

        return Arr::except($settings, ['theme']);
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
     * Normalize an incoming layout settings payload.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function normalizeSettings(array $settings): array
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

        if (array_key_exists('dashboard_widget_order', $settings) && is_array($settings['dashboard_widget_order'])) {
            $normalized['dashboard_widget_order'] = $this->normalizeWidgetOrder(
                $settings['dashboard_widget_order'],
                $this->widgetKeys(),
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
     * Get the default admin widget keys.
     *
     * @return array<int, string>
     */
    private function widgetKeys(): array
    {
        return array_keys($this->widgets());
    }
}
