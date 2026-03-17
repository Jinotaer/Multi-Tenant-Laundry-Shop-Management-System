@php
    $tenant = tenant();
    $layoutSettingsService = app(\App\Services\LayoutSettingsService::class);
    $workspaceDefaults = $layoutSettingsService->tenantDefaults($tenant);
    $theme = app(\App\Services\ThemeService::class)->getTenantTheme();
    $shopName = $tenant?->data['shop_name'] ?? 'LaundryTrack';
    $fontSizeValue = $layoutSettingsService->fontSizeValue($workspaceDefaults['font_size']);
    $radiusValue = $layoutSettingsService->borderRadiusValue($workspaceDefaults['border_radius']);
    $colorModeLabel = data_get(
        config('layout.options.color_mode'),
        $workspaceDefaults['color_mode'] . '.label',
        ucfirst($workspaceDefaults['color_mode']),
    );
@endphp

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-theme="{{ $workspaceDefaults['theme'] }}"
    data-color-mode="{{ $workspaceDefaults['color_mode'] }}"
    data-font-size="{{ $workspaceDefaults['font_size'] }}"
    data-border-radius="{{ $workspaceDefaults['border_radius'] }}"
    style="font-size: {{ $fontSizeValue }}; --tenant-radius: {{ $radiusValue }}; --tenant-theme-accent: {{ $theme['preview'] ?? '#6366f1' }}; --tenant-theme-accent-soft: {{ ($theme['preview'] ?? '#6366f1') . '18' }}; --tenant-theme-accent-soft-strong: {{ ($theme['preview'] ?? '#6366f1') . '30' }};"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">

        <title>{{ $shopName }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script>
            (() => {
                const colorMode = @js($workspaceDefaults['color_mode']);
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
        <div class="tenant-shell tenant-auth-shell">
            <div class="flex min-h-screen flex-col items-center justify-center px-4 py-6 sm:px-6">
                <div class="w-full sm:max-w-md">
                    <div class="tenant-auth-card px-6 py-6 sm:px-8 sm:py-8">
                        {{ $slot }}
                    </div>

                    <p class="mt-4 text-center text-xs font-medium uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">
                        {{ $shopName }} &middot; {{ $theme['label'] }} &middot; {{ $colorModeLabel }}
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>



