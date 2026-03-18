<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminLayoutSettingsRequest;
use App\Models\Admin;
use App\Services\AdminLayoutSettingsService;
use App\Services\ThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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

        if (! $admin instanceof Admin) {
            abort(403);
        }

        return view('admin.settings.theme', [
            'presets' => $themeService->getAllPresets(),
            'resolvedLayout' => $adminLayoutSettingsService->resolve($admin),
            'optionGroups' => $adminLayoutSettingsService->options(),
            'widgetCatalog' => $adminLayoutSettingsService->widgets(),
            'logoUrl' => $admin->logo_path ? asset('storage/'.$admin->logo_path) : null,
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

        abort_unless($admin instanceof Admin, 403);

        $validated = $request->validated();

        $admin->theme = $validated['theme'];
        $admin->layout_settings = $adminLayoutSettingsService->buildLayoutSettings($validated);
        $admin->save();

        return back()->with('success', 'Admin layout updated successfully.');
    }

    /**
     * Save admin layout settings from the live customizer via AJAX.
     */
    public function saveFromCustomizer(
        Request $request,
        AdminLayoutSettingsService $adminLayoutSettingsService
    ): JsonResponse {
        $admin = Auth::guard('admin')->user();

        abort_unless($admin instanceof Admin, 403);

        $themeKeys = array_keys(config('themes.presets', []));

        $data = $request->validate([
            'sidebar_position' => [
                'sometimes',
                'string',
                Rule::in(array_keys(config('layout.options.sidebar_position', []))),
            ],
            'topbar_behavior' => [
                'sometimes',
                'string',
                Rule::in(array_keys(config('layout.options.topbar_behavior', []))),
            ],
            'topbar_style' => [
                'sometimes',
                'string',
                Rule::in(array_keys(config('layout.options.topbar_style', []))),
            ],
            'sidebar_style' => [
                'sometimes',
                'string',
                Rule::in(array_keys(config('layout.options.sidebar_style', []))),
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
                Rule::in(array_keys(config('layout.options.border_radius', []))),
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
        ]);

        if (isset($data['theme'])) {
            $admin->theme = $data['theme'];
        }

        $existing = (array) ($admin->layout_settings ?? []);
        $admin->layout_settings = $adminLayoutSettingsService->buildLayoutSettings(
            array_merge($existing, array_diff_key($data, ['theme' => true]))
        );
        $admin->save();

        return response()->json(['saved' => true]);
    }

    /**
     * Reset admin layout settings from the live customizer.
     */
    public function resetFromCustomizer(): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        abort_unless($admin instanceof Admin, 403);

        $admin->theme = config('admin-layout.defaults.theme', config('themes.default'));
        $admin->layout_settings = null;
        $admin->save();

        return response()->json(['reset' => true]);
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

        abort_unless($admin instanceof Admin, 403);

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

        abort_unless($admin instanceof Admin, 403);

        if ($admin->logo_path && Storage::disk('public')->exists($admin->logo_path)) {
            Storage::disk('public')->delete($admin->logo_path);
        }

        $admin->logo_path = null;
        $admin->save();

        return back()->with('success', 'Logo removed successfully.');
    }
}
