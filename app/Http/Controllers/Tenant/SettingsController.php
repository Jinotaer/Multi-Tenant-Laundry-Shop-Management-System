<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
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
    public function index(ThemeService $themeService): View
    {
        $tenant = tenant();

        return view('tenant.settings.theme', [
            'presets' => $themeService->getAllPresets(),
            'currentTheme' => $tenant->theme ?? config('themes.default'),
            'logoUrl' => $tenant->logo_path ? Storage::disk('public')->url($tenant->logo_path) : null,
        ]);
    }

    /**
     * Update the tenant's theme preference.
     */
    public function updateTheme(Request $request): RedirectResponse
    {
        $request->validate([
            'theme' => ['required', 'string', 'in:'.implode(',', array_keys(config('themes.presets')))],
        ]);

        $tenant = tenant();
        $tenant->theme = $request->input('theme');
        $tenant->save();

        return back()->with('success', 'Theme updated successfully.');
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

        // Delete old logo if exists
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
