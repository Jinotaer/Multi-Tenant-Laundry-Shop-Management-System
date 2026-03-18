<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateTenantLayoutDefaultsRequest;
use App\Http\Requests\Tenant\UpdateUserLayoutPreferencesRequest;
use App\Services\LayoutSettingsService;
use App\Services\ThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display the tenant settings page.
     */
    public function index(
        ThemeService $themeService,
        LayoutSettingsService $layoutSettingsService,
    ): View {
        $tenant = tenant();
        $user = auth()->user();
        $tenantDefaults = $layoutSettingsService->tenantDefaults($tenant);
        $userOverrides = $layoutSettingsService->userOverrides($user);
        $resolvedSettings = $layoutSettingsService->resolve($tenant, $user);
        $logoUrl =
            $tenant->logo_path &&
            Storage::disk('public')->exists($tenant->logo_path)
                ? Storage::disk('public')->url($tenant->logo_path)
                : null;

        return view('tenant.settings.theme', [
            'presets' => $themeService->getAllPresets(),
            'optionGroups' => $layoutSettingsService->options(),
            'workspaceDefaults' => $tenantDefaults,
            'userOverrides' => $userOverrides,
            'resolvedSettings' => $resolvedSettings,
            'workspaceWidgets' => $layoutSettingsService->workspaceWidgets(),
            'availableWidgets' => $layoutSettingsService->availableWidgetsFor(
                $user,
            ),
            'canManageWorkspaceDefaults' => $layoutSettingsService->canManageWorkspaceDefaults(
                $user,
            ),
            'canManagePersonalPreferences' => $layoutSettingsService->canManagePersonalPreferences(
                $user,
            ),
            'canCustomizeWidgetOrder' => $layoutSettingsService->canCustomizeWidgetOrder(
                $user,
            ),
            'logoUrl' => $logoUrl,
        ]);
    }

    /**
     * Update the tenant's layout defaults.
     */
    public function updateTheme(
        UpdateTenantLayoutDefaultsRequest $request,
        LayoutSettingsService $layoutSettingsService,
    ): RedirectResponse {
        $validated = $request->validated();
        $tenant = tenant();

        $tenant->theme = $validated['theme'];
        $tenant->layout_settings = $layoutSettingsService->buildTenantLayoutSettings(
            $validated,
        );
        $tenant->save();

        return back()->with(
            'success',
            'Workspace layout defaults updated successfully.',
        );
    }

    /**
     * Update the authenticated user's layout preferences.
     */
    public function updatePreferences(
        UpdateUserLayoutPreferencesRequest $request,
        LayoutSettingsService $layoutSettingsService,
    ): RedirectResponse {
        $user = $request->user();

        abort_unless(
            $layoutSettingsService->canManagePersonalPreferences($user),
            403,
        );

        $overrides = $layoutSettingsService->buildUserOverrides(
            tenant(),
            $user,
            $request->validated(),
        );

        $user->layout_preferences = $overrides ?: null;
        $user->save();

        return back()->with(
            'success',
            'Your layout preferences were updated successfully.',
        );
    }

    /**
     * Reset the authenticated user's layout preferences.
     */
    public function resetPreferences(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            app(LayoutSettingsService::class)->canManagePersonalPreferences(
                $user,
            ),
            403,
        );

        $user->layout_preferences = null;
        $user->save();

        return back()->with(
            'success',
            'Your layout preferences were reset to the workspace defaults.',
        );
    }

    /**
     * Reset layout settings from the live customizer via AJAX.
     * Owners reset workspace defaults; staff/customers reset personal preferences.
     */
    public function resetFromCustomizer(
        Request $request,
        LayoutSettingsService $layoutSettingsService,
    ): JsonResponse {
        $user = $request->user();

        if ($layoutSettingsService->canManageWorkspaceDefaults($user)) {
            $tenant = tenant();
            $tenant->theme = config(
                'layout.defaults.theme',
                config('themes.default'),
            );
            $tenant->layout_settings = null;
            $tenant->save();

            return response()->json(['reset' => true]);
        }

        abort_unless(
            $layoutSettingsService->canManagePersonalPreferences($user),
            403,
        );

        $user->layout_preferences = null;
        $user->save();

        return response()->json(['reset' => true]);
    }

    /**
     * Update the tenant's logo.
     */
    public function updateLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,svg',
                'max:2048',
            ],
        ]);

        $tenant = tenant();

        if (
            $tenant->logo_path &&
            Storage::disk('public')->exists($tenant->logo_path)
        ) {
            Storage::disk('public')->delete($tenant->logo_path);
        }

        $path = $request->file('logo')->store('logos/tenants', 'public');

        $tenant->logo_path = $path;
        $tenant->save();

        return back()->with('success', 'Logo updated successfully.');
    }

    /**
     * Remove the tenant's logo.
     */
    public function removeLogo(): RedirectResponse
    {
        $tenant = tenant();

        if (
            $tenant->logo_path &&
            Storage::disk('public')->exists($tenant->logo_path)
        ) {
            Storage::disk('public')->delete($tenant->logo_path);
        }

        $tenant->logo_path = null;
        $tenant->save();

        return back()->with('success', 'Logo removed successfully.');
    }

    /**
     * Save layout settings from the live customizer via AJAX.
     * Owners save to tenant layout_settings; staff/customers save to user layout_preferences.
     */
    public function saveFromCustomizer(
        Request $request,
        LayoutSettingsService $layoutSettingsService,
    ): \Illuminate\Http\JsonResponse {
        $user = $request->user();
        $tenant = tenant();

        $themeKeys = array_keys(config('themes.presets', []));
        $widgetKeys = array_keys(config('layout.widgets', []));

        $data = $request->validate([
            'sidebar_position' => [
                'sometimes',
                'string',
                Rule::in(
                    array_keys(config('layout.options.sidebar_position', [])),
                ),
            ],
            'topbar_behavior' => [
                'sometimes',
                'string',
                Rule::in(
                    array_keys(config('layout.options.topbar_behavior', [])),
                ),
            ],
            'topbar_style' => [
                'sometimes',
                'string',
                Rule::in(array_keys(config('layout.options.topbar_style', []))),
            ],
            'sidebar_style' => [
                'sometimes',
                'string',
                Rule::in(
                    array_keys(config('layout.options.sidebar_style', [])),
                ),
            ],
            'color_mode' => [
                'sometimes',
                'string',
                Rule::in(array_keys(config('layout.options.color_mode', []))),
            ],
            'theme' => ['sometimes', 'string', Rule::in($themeKeys)],
            'font_size' => [
                'sometimes',
                'string',
                Rule::in(array_keys(config('layout.options.font_size', []))),
            ],
            'border_radius' => [
                'sometimes',
                'string',
                Rule::in(
                    array_keys(config('layout.options.border_radius', [])),
                ),
            ],
            'icon_size' => [
                'sometimes',
                'string',
                Rule::in(array_keys(config('layout.options.icon_size', []))),
            ],
            'icon_stroke' => [
                'sometimes',
                'string',
                Rule::in(array_keys(config('layout.options.icon_stroke', []))),
            ],
            'logo_visibility' => ['sometimes', 'boolean'],
            'dashboard_widget_order' => ['sometimes', 'array'],
            'dashboard_widget_order.*' => ['string', Rule::in($widgetKeys)],
        ]);

        if ($layoutSettingsService->canManageWorkspaceDefaults($user)) {
            // Owner: save theme + layout_settings on tenant
            if (isset($data['theme'])) {
                $tenant->theme = $data['theme'];
            }
            $existing = (array) ($tenant->layout_settings ?? []);
            $tenant->layout_settings = array_merge(
                $existing,
                array_diff_key($data, ['theme' => true]),
            );
            $tenant->save();
        } else {
            // Staff / Customer: save personal overrides
            $existing = (array) ($user->layout_preferences ?? []);
            $user->layout_preferences = array_merge($existing, $data);
            $user->save();
        }

        return response()->json(['saved' => true]);
    }
}
