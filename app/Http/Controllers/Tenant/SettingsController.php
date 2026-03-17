<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateTenantLayoutDefaultsRequest;
use App\Http\Requests\Tenant\UpdateUserLayoutPreferencesRequest;
use App\Services\LayoutSettingsService;
use App\Services\ThemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display the tenant settings page.
     */
    public function index(ThemeService $themeService, LayoutSettingsService $layoutSettingsService): View
    {
        $tenant = tenant();
        $user = auth()->user();
        $tenantDefaults = $layoutSettingsService->tenantDefaults($tenant);
        $userOverrides = $layoutSettingsService->userOverrides($user);
        $resolvedSettings = $layoutSettingsService->resolve($tenant, $user);
        $logoUrl = $tenant->logo_path && Storage::disk('public')->exists($tenant->logo_path)
            ? asset('storage/'.ltrim($tenant->logo_path, '/'))
            : null;

        return view('tenant.settings.theme', [
            'presets' => $themeService->getAllPresets(),
            'optionGroups' => $layoutSettingsService->options(),
            'workspaceDefaults' => $tenantDefaults,
            'userOverrides' => $userOverrides,
            'resolvedSettings' => $resolvedSettings,
            'workspaceWidgets' => $layoutSettingsService->workspaceWidgets(),
            'availableWidgets' => $layoutSettingsService->availableWidgetsFor($user),
            'canManageWorkspaceDefaults' => $layoutSettingsService->canManageWorkspaceDefaults($user),
            'canManagePersonalPreferences' => $layoutSettingsService->canManagePersonalPreferences($user),
            'canCustomizeWidgetOrder' => $layoutSettingsService->canCustomizeWidgetOrder($user),
            'logoUrl' => $logoUrl,
        ]);
    }

    /**
     * Update the tenant's layout defaults.
     */
    public function updateTheme(
        UpdateTenantLayoutDefaultsRequest $request,
        LayoutSettingsService $layoutSettingsService
    ): RedirectResponse {
        $validated = $request->validated();
        $tenant = tenant();

        $tenant->theme = $validated['theme'];
        $tenant->layout_settings = $layoutSettingsService->buildTenantLayoutSettings($validated);
        $tenant->save();

        return back()->with('success', 'Workspace layout defaults updated successfully.');
    }

    /**
     * Update the authenticated user's layout preferences.
     */
    public function updatePreferences(
        UpdateUserLayoutPreferencesRequest $request,
        LayoutSettingsService $layoutSettingsService
    ): RedirectResponse {
        $user = $request->user();

        abort_unless($layoutSettingsService->canManagePersonalPreferences($user), 403);

        $overrides = $layoutSettingsService->buildUserOverrides(
            tenant(),
            $user,
            $request->validated(),
        );

        $user->layout_preferences = $overrides ?: null;
        $user->save();

        return back()->with('success', 'Your layout preferences were updated successfully.');
    }

    /**
     * Reset the authenticated user's layout preferences.
     */
    public function resetPreferences(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            app(LayoutSettingsService::class)->canManagePersonalPreferences($user),
            403,
        );

        $user->layout_preferences = null;
        $user->save();

        return back()->with('success', 'Your layout preferences were reset to the workspace defaults.');
    }

    /**
     * Update the tenant's logo.
     */
    public function updateLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,svg', 'max:2048'],
        ]);

        $tenant = tenant();

        if ($tenant->logo_path && Storage::disk('public')->exists($tenant->logo_path)) {
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

        if ($tenant->logo_path && Storage::disk('public')->exists($tenant->logo_path)) {
            Storage::disk('public')->delete($tenant->logo_path);
        }

        $tenant->logo_path = null;
        $tenant->save();

        return back()->with('success', 'Logo removed successfully.');
    }
}
