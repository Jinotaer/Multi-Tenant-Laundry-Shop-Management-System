<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminLayoutSettingsRequest;
use App\Services\AdminLayoutSettingsService;
use App\Services\ThemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display the admin settings page.
     */
    public function index(
        ThemeService $themeService,
        AdminLayoutSettingsService $adminLayoutSettingsService
    ): View {
        $admin = Auth::guard('admin')->user();

        return view('admin.settings.theme', [
            'presets' => $themeService->getAllPresets(),
            'resolvedLayout' => $adminLayoutSettingsService->resolve($admin),
            'optionGroups' => $adminLayoutSettingsService->options(),
            'widgetCatalog' => $adminLayoutSettingsService->widgets(),
            'logoUrl' => $admin->logo_path ? Storage::disk('public')->url($admin->logo_path) : null,
        ]);
    }

    /**
     * Update the admin's theme preference.
     */
    public function updateTheme(
        UpdateAdminLayoutSettingsRequest $request,
        AdminLayoutSettingsService $adminLayoutSettingsService
    ): RedirectResponse {
        $admin = Auth::guard('admin')->user();
        $validated = $request->validated();

        $admin->theme = $validated['theme'];
        $admin->layout_settings = $adminLayoutSettingsService->buildLayoutSettings($validated);
        $admin->save();

        return back()->with('success', 'Admin layout updated successfully.');
    }

    /**
     * Update the admin panel logo.
     */
    public function updateLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,svg', 'max:2048'],
        ]);

        $admin = Auth::guard('admin')->user();

        // Delete old logo if exists
        if ($admin->logo_path && Storage::disk('public')->exists($admin->logo_path)) {
            Storage::disk('public')->delete($admin->logo_path);
        }

        $path = $request->file('logo')->store('logos/admin', 'public');

        $admin->logo_path = $path;
        $admin->save();

        return back()->with('success', 'Logo updated successfully.');
    }

    /**
     * Remove the admin panel logo.
     */
    public function removeLogo(): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();

        if ($admin->logo_path && Storage::disk('public')->exists($admin->logo_path)) {
            Storage::disk('public')->delete($admin->logo_path);
        }

        $admin->logo_path = null;
        $admin->save();

        return back()->with('success', 'Logo removed successfully.');
    }
}
