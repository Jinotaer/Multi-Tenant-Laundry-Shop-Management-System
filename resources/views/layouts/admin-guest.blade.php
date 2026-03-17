@php
    use Illuminate\Support\Facades\Auth;

    $layoutSettingsService = app(\App\Services\AdminLayoutSettingsService::class);
    $admin = Auth::guard('admin')->user() ?? \App\Models\Admin::first();
    $layoutDefaults = $layoutSettingsService->resolve($admin);
    $themePresets = app(\App\Services\ThemeService::class)->getAllPresets();
    $defaultThemeKey = config('themes.default', 'indigo');
    $theme = $themePresets[$layoutDefaults['theme']] ?? $themePresets[$defaultThemeKey] ?? [
        'label' => 'Indigo',
        'preview' => '#6366f1',
    ];
    $fontSizeValue = $layoutSettingsService->fontSizeValue($layoutDefaults['font_size']);
    $radiusValue = $layoutSettingsService->borderRadiusValue($layoutDefaults['border_radius']);
@endphp

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-theme="{{ $layoutDefaults['theme'] }}"
    data-color-mode="{{ $layoutDefaults['color_mode'] }}"
    data-font-size="{{ $layoutDefaults['font_size'] }}"
    data-border-radius="{{ $layoutDefaults['border_radius'] }}"
    style="font-size: {{ $fontSizeValue }}; --tenant-radius: {{ $radiusValue }}; --tenant-theme-accent: {{ $theme['preview'] ?? '#6366f1' }}; --tenant-theme-accent-soft: {{ ($theme['preview'] ?? '#6366f1') . '18' }}; --tenant-theme-accent-soft-strong: {{ ($theme['preview'] ?? '#6366f1') . '30' }};"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">

        <title>{{ config('app.name', 'LaundryTrack') }} - Admin</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script>
            (() => {
                const colorMode = @js($layoutDefaults['color_mode']);
                const root = document.documentElement;
                const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

                const applyColorMode = () => {
                    const shouldUseDark = colorMode === 'dark' || (colorMode === 'system' && mediaQuery.matches);
                    root.classList.toggle('dark', shouldUseDark);
                    root.dataset.colorMode = colorMode;
                };

                applyColorMode();

                if (colorMode === 'system') {
                    if (typeof mediaQuery.addEventListener === 'function') {
                        mediaQuery.addEventListener('change', applyColorMode);
                    } else if (typeof mediaQuery.addListener === 'function') {
                        mediaQuery.addListener(applyColorMode);
                    }
                }
            })();
        </script>
    </head>
    <body class="font-sans antialiased">
        <div class="tenant-shell tenant-auth-shell" x-data="{
            colorMode: @js($layoutDefaults['color_mode']),
            isDark: document.documentElement.classList.contains('dark'),
            toggle() {
                this.isDark = !this.isDark;
                document.documentElement.classList.toggle('dark', this.isDark);
                this.colorMode = this.isDark ? 'dark' : 'light';
            }
        }">
            <button
                type="button"
                @click="toggle()"
                class="fixed right-4 top-4 z-50 flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white/80 text-gray-500 shadow-sm backdrop-blur transition hover:text-gray-700 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-400 dark:hover:text-slate-200"
                :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            >
                <svg x-show="!isDark" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                </svg>
                <svg x-show="isDark" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                </svg>
            </button>

            <div class="flex min-h-screen flex-col items-center justify-center px-4 py-6 sm:px-6">
                <div class="w-full sm:max-w-md">
                    <div class="tenant-auth-card px-6 py-6 sm:px-8 sm:py-8">
                        {{ $slot }}
                    </div>

                    <p class="mt-4 text-center text-xs font-medium uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">
                        Admin Portal &middot; {{ $theme['label'] }}
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>
