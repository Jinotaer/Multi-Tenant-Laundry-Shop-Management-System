@php
    use App\Models\TenantRegistration;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Storage;

    $layoutSettingsService = app(\App\Services\AdminLayoutSettingsService::class);
    /** @var \App\Models\Admin|null $admin */
    $admin = Auth::guard('admin')->user();
    $theme = app(\App\Services\ThemeService::class)->getAdminTheme();
    $resolvedLayout = $layoutSettingsService->resolve($admin);
    $logoUrl = $admin?->logo_path && Storage::disk('public')->exists($admin->logo_path)
        ? asset('storage/' . $admin->logo_path)
        : null;
    $pendingCount = TenantRegistration::where('status', 'pending')->count();
    $isSidebarRight = $resolvedLayout['sidebar_position'] === 'right';
    $isCompactSidebar = $resolvedLayout['sidebar_style'] === 'compact';
    $isFloatingSidebar = $resolvedLayout['sidebar_style'] === 'floating';
    $mobileClosedSidebarClass = $isSidebarRight ? 'translate-x-full' : '-translate-x-full';
    $desktopSidebarWidthClass = $isCompactSidebar ? 'lg:w-20' : 'lg:w-72';
    $contentOffsetClass = match (true) {
        $isSidebarRight && $isCompactSidebar => 'lg:pr-28',
        $isSidebarRight && $isFloatingSidebar => 'lg:pr-[22rem]',
        $isSidebarRight => 'lg:pr-72',
        ! $isSidebarRight && $isCompactSidebar => 'lg:pl-28',
        ! $isSidebarRight && $isFloatingSidebar => 'lg:pl-[22rem]',
        default => 'lg:pl-72',
    };
    $desktopSidebarPositionClass = $isSidebarRight ? 'lg:right-0 lg:left-auto' : 'lg:left-0';
    $mobileSidebarPositionClass = $isSidebarRight ? 'right-0 left-auto' : 'left-0';
    $floatingDesktopPositionClass = $isFloatingSidebar
        ? ($isSidebarRight ? 'lg:right-4 lg:left-auto lg:inset-y-4' : 'lg:left-4 lg:inset-y-4')
        : '';
    $sidebarSurfaceClasses = $isFloatingSidebar
        ? 'rounded-3xl border border-gray-200 bg-white/95 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/95'
        : (($isSidebarRight ? 'border-l' : 'border-r') . ' border-gray-200 bg-white dark:border-slate-800 dark:bg-slate-900');
    $logoVisible = $resolvedLayout['logo_visibility'] && $logoUrl !== null;
    $topbarSurfaceClass = match ($resolvedLayout['topbar_style']) {
        'card' => 'tenant-topbar tenant-topbar-card px-4 py-4 sm:px-6',
        'accent' => 'tenant-topbar tenant-topbar-accent px-4 py-4 sm:px-6',
        default => 'tenant-topbar tenant-topbar-minimal px-4 py-4 sm:px-6',
    };
    $topbarSpacingClass = in_array($resolvedLayout['topbar_style'], ['card', 'accent'], true) ? 'pt-4 pb-4' : 'pb-4';
    $topbarWrapperClass = $resolvedLayout['topbar_behavior'] === 'sticky'
        ? "sticky top-0 z-30 {$topbarSpacingClass}"
        : $topbarSpacingClass;
    $navLabelVisibilityClass = $isCompactSidebar ? 'lg:hidden' : '';
    $navAlignmentClass = $isCompactSidebar ? 'lg:justify-center' : '';
    $iconSpacingClass = $isCompactSidebar ? 'mr-3 h-5 w-5 lg:mr-0' : 'mr-3 h-5 w-5';
    $activeNavClass = 'tenant-nav-active';
    $inactiveNavClass = 'text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white';
    $layoutOptions = $layoutSettingsService->options();
    $themePresets = config('themes.presets', []);
    $themePreviewColors = [];

    foreach ($themePresets as $presetKey => $preset) {
        $themePreviewColors[$presetKey] = $preset['preview'];
    }

    $saveRoute = route('admin.settings.layout.save');
    $resetRoute = route('admin.settings.layout.reset');
    $csrfToken = csrf_token();
@endphp

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-theme="{{ $resolvedLayout['theme'] }}"
    data-sidebar-position="{{ $resolvedLayout['sidebar_position'] }}"
    data-topbar-behavior="{{ $resolvedLayout['topbar_behavior'] }}"
    data-topbar-style="{{ $resolvedLayout['topbar_style'] }}"
    data-sidebar-style="{{ $resolvedLayout['sidebar_style'] }}"
    data-color-mode="{{ $resolvedLayout['color_mode'] }}"
    data-font-size="{{ $resolvedLayout['font_size'] }}"
    data-border-radius="{{ $resolvedLayout['border_radius'] }}"
    data-icon-size="{{ $resolvedLayout['icon_size'] }}"
    data-icon-stroke="{{ $resolvedLayout['icon_stroke'] }}"
    data-logo-visibility="{{ $resolvedLayout['logo_visibility'] ? 'true' : 'false' }}"
    style="font-size: {{ $layoutSettingsService->fontSizeValue($resolvedLayout['font_size']) }}; --tenant-radius: {{ $layoutSettingsService->borderRadiusValue($resolvedLayout['border_radius']) }}; --tenant-icon-size: {{ $layoutSettingsService->iconSizeValue($resolvedLayout['icon_size']) }}; --tenant-icon-stroke: {{ $layoutSettingsService->iconStrokeValue($resolvedLayout['icon_stroke']) }}; --tenant-theme-accent: {{ $theme['preview'] ?? '#6366f1' }}; --tenant-theme-accent-soft: {{ ($theme['preview'] ?? '#6366f1') . '18' }}; --tenant-theme-accent-soft-strong: {{ ($theme['preview'] ?? '#6366f1') . '30' }};"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">

        <title>{{ config('app.name', 'LaundryTrack') }} - Admin</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script>
            (() => {
                const colorMode = @js($resolvedLayout['color_mode']);
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

        <style>
            @media (min-width: 1024px) {
                .tenant-shell[data-preview-sidebar-style='compact'] .admin-nav-list > a {
                    justify-content: center;
                }

                .tenant-shell[data-preview-sidebar-style='compact'] .admin-nav-list .admin-nav-label,
                .tenant-shell[data-preview-sidebar-style='compact'] .admin-nav-list .admin-nav-badge {
                    display: none !important;
                }

                .tenant-shell[data-preview-sidebar-style='compact'] .admin-nav-list .admin-nav-item-icon {
                    margin-right: 0 !important;
                }

                .tenant-shell[data-preview-sidebar-style='compact'] .admin-nav-list > a > div {
                    justify-content: center;
                }

                .tenant-shell[data-preview-sidebar-style='compact'] .admin-sidebar-brand-label,
                .tenant-shell[data-preview-sidebar-style='compact'] .admin-sidebar-user-meta {
                    display: none !important;
                }
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div
            class="tenant-shell min-h-screen"
            x-data="{
                sidebarOpen: false,
                customizerOpen: false,
                saving: false,
                saved: false,
                saveError: '',
                logoLoadFailed: false,
                themeColors: @js($themePreviewColors),
                prefs: @js([
                    'theme' => $resolvedLayout['theme'],
                    'sidebar_position' => $resolvedLayout['sidebar_position'],
                    'topbar_behavior' => $resolvedLayout['topbar_behavior'],
                    'topbar_style' => $resolvedLayout['topbar_style'],
                    'sidebar_style' => $resolvedLayout['sidebar_style'],
                    'color_mode' => $resolvedLayout['color_mode'],
                    'font_size' => $resolvedLayout['font_size'],
                    'border_radius' => $resolvedLayout['border_radius'],
                    'icon_size' => $resolvedLayout['icon_size'],
                    'icon_stroke' => $resolvedLayout['icon_stroke'],
                    'logo_visibility' => (bool) $resolvedLayout['logo_visibility'],
                ]),
                init() {
                    this.applyPreview();
                },
                get isTop() {
                    return this.prefs.sidebar_position === 'top';
                },
                get isRight() {
                    return this.prefs.sidebar_position === 'right';
                },
                get isCompact() {
                    return this.prefs.sidebar_style === 'compact';
                },
                get isFloating() {
                    return this.prefs.sidebar_style === 'floating';
                },
                get asideLayoutClass() {
                    if (this.isTop) {
                        return 'fixed inset-x-0 top-0 z-50 flex h-16 items-center gap-2 border-b border-gray-200 bg-white px-2 dark:border-slate-800 dark:bg-slate-900';
                    }

                    const width = this.isCompact ? 'lg:w-20' : 'lg:w-72';
                    const position = this.isRight
                        ? 'right-0 left-auto lg:right-0 lg:left-auto'
                        : 'left-0 lg:left-0 lg:right-auto';
                    const floatingPosition = this.isFloating
                        ? (this.isRight ? 'lg:right-4 lg:left-auto lg:inset-y-4' : 'lg:left-4 lg:inset-y-4')
                        : '';
                    const surface = this.isFloating
                        ? 'rounded-3xl border border-gray-200 bg-white/95 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/95'
                        : (this.isRight
                            ? 'border-l border-gray-200 bg-white dark:border-slate-800 dark:bg-slate-900'
                            : 'border-r border-gray-200 bg-white dark:border-slate-800 dark:bg-slate-900');

                    return `fixed inset-y-0 ${position} ${floatingPosition} z-50 w-72 ${width} flex flex-col ${surface}`;
                },
                get showLogo() {
                    return {{ $logoUrl ? 'true' : 'false' }} && this.prefs.logo_visibility && !this.logoLoadFailed;
                },
                get asideTranslateClass() {
                    if (this.isTop) {
                        return 'translate-y-0';
                    }

                    if (this.sidebarOpen) {
                        return 'translate-x-0';
                    }

                    return this.isRight ? 'translate-x-full lg:translate-x-0' : '-translate-x-full lg:translate-x-0';
                },
                get contentWrapperClass() {
                    if (this.isTop) {
                        return 'pt-20';
                    }

                    if (this.isRight) {
                        if (this.isCompact) {
                            return 'lg:pr-28';
                        }

                        if (this.isFloating) {
                            return 'lg:pr-[22rem]';
                        }

                        return 'lg:pr-72';
                    }

                    if (this.isCompact) {
                        return 'lg:pl-28';
                    }

                    if (this.isFloating) {
                        return 'lg:pl-[22rem]';
                    }

                    return 'lg:pl-72';
                },
                get navClass() {
                    if (this.isTop) {
                        return 'admin-nav-list flex flex-1 flex-row items-center gap-1 overflow-x-auto !space-y-0 !px-3 !py-2 whitespace-nowrap';
                    }

                    return 'admin-nav-list flex-1 space-y-1 overflow-y-auto px-4 py-4';
                },
                get topbarWrapperClass() {
                    const spacing = ['card', 'accent'].includes(this.prefs.topbar_style) ? 'pt-4 pb-4' : 'pb-4';

                    if (this.isTop) {
                        return spacing;
                    }

                    if (this.prefs.topbar_behavior === 'sticky') {
                        return `sticky top-0 z-30 ${spacing}`;
                    }

                    return spacing;
                },
                get topbarSurfaceClass() {
                    if (this.prefs.topbar_style === 'card') {
                        return 'tenant-topbar tenant-topbar-card px-4 py-4 sm:px-6';
                    }

                    if (this.prefs.topbar_style === 'accent') {
                        return 'tenant-topbar tenant-topbar-accent px-4 py-4 sm:px-6';
                    }

                    return 'tenant-topbar tenant-topbar-minimal px-4 py-4 sm:px-6';
                },
                get topbarInnerClass() {
                    if (this.isCompact) {
                        return 'flex flex-wrap items-center gap-3 sm:gap-4';
                    }

                    return 'flex items-center gap-4';
                },
                get topbarTitleClass() {
                    if (this.isCompact && this.isRight) {
                        return 'min-w-0 flex-1 text-right';
                    }

                    if (this.isCompact) {
                        return 'min-w-0 flex-1';
                    }

                    if (this.isRight) {
                        return 'min-w-0 flex-1 text-right';
                    }

                    return 'min-w-0 flex-1';
                },
                get topbarUserClass() {
                    if (this.isCompact && this.isRight) {
                        return 'hidden items-center gap-2 lg:order-first lg:flex';
                    }

                    if (this.isCompact) {
                        return 'hidden items-center gap-2 lg:flex';
                    }

                    if (this.isRight) {
                        return 'hidden items-center gap-3 sm:order-first sm:flex';
                    }

                    return 'hidden items-center gap-3 sm:flex';
                },
                get customizerDockClass() {
                    return this.isRight ? 'left-4' : 'right-4';
                },
                optionPillClass(active) {
                    if (active) {
                        return 'tenant-primary-action border-transparent text-white';
                    }

                    return 'border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800';
                },
                applyPreview() {
                    const root = document.documentElement;

                    root.dataset.theme = this.prefs.theme;
                    root.dataset.sidebarPosition = this.prefs.sidebar_position;
                    root.dataset.topbarBehavior = this.prefs.topbar_behavior;
                    root.dataset.topbarStyle = this.prefs.topbar_style;
                    root.dataset.sidebarStyle = this.prefs.sidebar_style;
                    root.dataset.colorMode = this.prefs.color_mode;
                    root.dataset.fontSize = this.prefs.font_size;
                    root.dataset.borderRadius = this.prefs.border_radius;
                    root.dataset.iconSize = this.prefs.icon_size;
                    root.dataset.iconStroke = this.prefs.icon_stroke;
                    root.dataset.logoVisibility = this.prefs.logo_visibility ? 'true' : 'false';

                    const fontSizes = {
                        sm: '15px',
                        base: '16px',
                        lg: '17px',
                    };

                    const borderRadius = {
                        md: '0.75rem',
                        lg: '1rem',
                        xl: '1.5rem',
                    };

                    const iconSize = {
                        sm: '1rem',
                        base: '1.25rem',
                        lg: '1.5rem',
                    };

                    const iconStroke = {
                        thin: '1.25',
                        base: '1.5',
                        bold: '2',
                    };

                    const accent = this.themeColors[this.prefs.theme] ?? '#6366f1';

                    root.style.fontSize = fontSizes[this.prefs.font_size] ?? '16px';
                    root.style.setProperty('--tenant-radius', borderRadius[this.prefs.border_radius] ?? '1rem');
                    root.style.setProperty('--tenant-icon-size', iconSize[this.prefs.icon_size] ?? '1.25rem');
                    root.style.setProperty('--tenant-icon-stroke', iconStroke[this.prefs.icon_stroke] ?? '1.5');
                    root.style.setProperty('--tenant-theme-accent', accent);
                    root.style.setProperty('--tenant-theme-accent-soft', accent + '18');
                    root.style.setProperty('--tenant-theme-accent-soft-strong', accent + '30');

                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    const useDark = this.prefs.color_mode === 'dark' || (this.prefs.color_mode === 'system' && prefersDark);
                    root.classList.toggle('dark', useDark);
                },
                async saveLayout() {
                    this.saving = true;
                    this.saveError = '';

                    try {
                        const response = await fetch('{{ $saveRoute }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ $csrfToken }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                ...this.prefs,
                                logo_visibility: !!this.prefs.logo_visibility,
                            }),
                        });

                        if (! response.ok) {
                            this.saveError = 'Unable to save admin layout. Please try again.';
                            return;
                        }

                        this.saved = true;
                        setTimeout(() => this.saved = false, 2500);
                    } catch (_error) {
                        this.saveError = 'Network error while saving admin layout.';
                    } finally {
                        this.saving = false;
                    }
                },
                async resetLayout() {
                    if (! window.confirm('Reset admin layout settings to defaults?')) {
                        return;
                    }

                    await fetch('{{ $resetRoute }}', {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ $csrfToken }}',
                            'Accept': 'application/json',
                        },
                    });

                    window.location.reload();
                },
            }"
            :data-preview-sidebar-position="prefs.sidebar_position"
            :data-preview-sidebar-style="prefs.sidebar_style"
        >
            <button
                type="button"
                x-on:click="customizerOpen = ! customizerOpen"
                class="tenant-primary-action fixed bottom-4 z-[80] inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold shadow-lg"
                :class="customizerDockClass"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M3.75 6h1.5m5.25 0a2.25 2.25 0 1 1-4.5 0m4.5 0a2.25 2.25 0 1 0-4.5 0m4.5 12h9.75m-9.75 0a2.25 2.25 0 1 1-4.5 0m4.5 0a2.25 2.25 0 1 0-4.5 0m4.5-6h9.75m-9.75 0a2.25 2.25 0 1 1-4.5 0m4.5 0a2.25 2.25 0 1 0-4.5 0" /></svg>
                <span>Customize Layout</span>
            </button>

            <section
                x-show="customizerOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-2"
                class="fixed bottom-20 z-[80] w-[24rem] max-h-[75vh] overflow-y-auto rounded-2xl border border-gray-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
                :class="customizerDockClass"
                x-cloak
            >
                <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100">Admin Layout Customization</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Preview updates instantly, then save when ready.</p>

                <div class="mt-4 space-y-4">
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-slate-400">Theme Color</p>
                        <div class="grid grid-cols-6 gap-2">
                            @foreach ($themePresets as $presetKey => $preset)
                                <button
                                    type="button"
                                    x-on:click="prefs.theme = '{{ $presetKey }}'; applyPreview()"
                                    class="h-8 w-8 rounded-full border-2 transition"
                                    :class="prefs.theme === '{{ $presetKey }}' ? 'border-gray-900 dark:border-slate-100' : 'border-transparent hover:border-gray-300 dark:hover:border-slate-600'"
                                    style="background-color: {{ $preset['preview'] }}"
                                    title="{{ $preset['label'] }}"
                                ></button>
                            @endforeach
                        </div>
                    </div>

                    @foreach (['sidebar_position', 'topbar_behavior', 'topbar_style', 'sidebar_style', 'color_mode', 'font_size', 'border_radius', 'icon_size', 'icon_stroke'] as $optionKey)
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-slate-400">{{ str_replace('_', ' ', $optionKey) }}</p>
                            <div class="grid {{ in_array($optionKey, ['topbar_behavior'], true) ? 'grid-cols-2' : 'grid-cols-3' }} gap-2">
                                @foreach ($layoutOptions[$optionKey] as $value => $option)
                                    <button type="button" x-on:click="prefs.{{ $optionKey }} = '{{ (string) $value }}'; applyPreview()" class="rounded-lg border px-2.5 py-2 text-xs font-medium transition" :class="optionPillClass(prefs.{{ $optionKey }} === '{{ (string) $value }}')">{{ $option['label'] }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-slate-400">Logo Visibility</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($layoutOptions['logo_visibility'] as $value => $option)
                                <button type="button" x-on:click="prefs.logo_visibility = {{ (string) $value === '1' ? 'true' : 'false' }}; applyPreview()" class="rounded-lg border px-2.5 py-2 text-xs font-medium transition" :class="optionPillClass(prefs.logo_visibility === {{ (string) $value === '1' ? 'true' : 'false' }})">{{ $option['label'] }}</button>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-3 dark:border-slate-700">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-slate-400">Admin Logo</p>

                        <div class="space-y-3">
                            <form method="POST" action="{{ route('admin.settings.logo') }}" enctype="multipart/form-data" class="space-y-2">
                                @csrf

                                <input type="file" name="logo" accept="image/jpeg,image/png,image/svg+xml" class="block w-full text-xs text-gray-500 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-gray-700 hover:file:bg-gray-200 dark:file:bg-slate-800 dark:file:text-slate-100 dark:hover:file:bg-slate-700">

                                <button type="submit" class="tenant-primary-action rounded-md px-3 py-1.5 text-xs font-semibold">Upload Logo</button>
                            </form>

                            @if ($logoUrl)
                                <form method="POST" action="{{ route('admin.settings.logo.remove') }}">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-slate-700 dark:text-slate-100 dark:hover:bg-slate-800">Remove Logo</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-end gap-2">
                    <button type="button" x-on:click="resetLayout()" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-slate-700 dark:text-slate-100 dark:hover:bg-slate-800">Reset</button>
                    <button type="button" x-on:click="saveLayout()" :disabled="saving" class="tenant-primary-action rounded-md px-3 py-1.5 text-xs font-semibold disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-show="! saving">Save</span>
                        <span x-show="saving">Saving...</span>
                    </button>
                </div>

                <p x-show="saved" class="mt-2 text-xs text-emerald-600 dark:text-emerald-400">Admin layout saved.</p>
                <p x-show="saveError" x-text="saveError" class="mt-2 text-xs text-red-600 dark:text-red-400"></p>
            </section>

            <div x-show="sidebarOpen && !isTop" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-40 bg-gray-950/60 lg:hidden" x-on:click="sidebarOpen = false" x-cloak></div>

            <aside :class="[asideLayoutClass, asideTranslateClass]" class="transform transition-transform duration-300 ease-in-out">
                <div class="flex min-h-[5.5rem] items-center justify-between border-b border-gray-200 px-6 pt-5 pb-6 dark:border-slate-800" :class="isTop ? '!h-16 !border-b-0 !border-r !px-3 !py-0' : ''">
                    <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-2">
                        @if ($logoUrl)
                            <img x-show="showLogo" x-on:error="logoLoadFailed = true" src="{{ $logoUrl }}" alt="Admin Logo" class="h-8 w-8 flex-shrink-0 rounded-xl object-contain" x-cloak>
                        @endif
                        <span class="tenant-wordmark tenant-wordmark-sidebar admin-sidebar-brand-label truncate {{ $navLabelVisibilityClass }}">
                            <span>Laundry</span><span class="tenant-wordmark-accent">Track</span>
                        </span>
                    </a>
                    <button x-show="!isTop" x-on:click="sidebarOpen = false" class="text-gray-400 hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-100 lg:hidden">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <nav :class="navClass" class="admin-nav-list flex-1 space-y-1 overflow-y-auto px-4 py-4">
                    <a href="{{ route('admin.dashboard') }}" title="Dashboard" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $navAlignmentClass }} {{ request()->routeIs('admin.dashboard') ? $activeNavClass : $inactiveNavClass }}">
                        <svg class="admin-nav-item-icon {{ $iconSpacingClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                        <span class="admin-nav-label {{ $navLabelVisibilityClass }}">Dashboard</span>
                    </a>

                    <a href="{{ route('admin.registrations.index') }}" title="Shop Registrations" class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('admin.registrations.*') ? $activeNavClass : $inactiveNavClass }}">
                        <div class="flex items-center {{ $navAlignmentClass }}">
                            <svg class="admin-nav-item-icon {{ $iconSpacingClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            <span class="admin-nav-label {{ $navLabelVisibilityClass }}">Shop Registrations</span>
                        </div>
                        @if ($pendingCount > 0)
                            <span class="admin-nav-badge ml-2 inline-flex items-center justify-center rounded-full bg-yellow-400 px-2 py-0.5 text-xs font-medium text-white {{ $navLabelVisibilityClass }}">
                                {{ $pendingCount }}
                            </span>
                        @endif
                    </a>

                    <a href="{{ route('admin.subscription-plans.index') }}" title="Subscription Plans" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $navAlignmentClass }} {{ request()->routeIs('admin.subscription-plans.*') ? $activeNavClass : $inactiveNavClass }}">
                        <svg class="admin-nav-item-icon {{ $iconSpacingClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                        <span class="admin-nav-label {{ $navLabelVisibilityClass }}">Subscription Plans</span>
                    </a>

                    <a href="{{ route('admin.tenants.index') }}" title="Shops" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $navAlignmentClass }} {{ request()->routeIs('admin.tenants.*') ? $activeNavClass : $inactiveNavClass }}">
                        <svg class="admin-nav-item-icon {{ $iconSpacingClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016A3.001 3.001 0 0021 9.349m-18 0a2.993 2.993 0 003.141.026A2.98 2.98 0 009.75 10.5c1.07 0 2.028-.447 2.711-1.164A3.422 3.422 0 0014.25 10.5a2.98 2.98 0 003.609-1.125A3.001 3.001 0 0021 9.349M3 9.349V4.875A1.125 1.125 0 014.125 3.75h15.75A1.125 1.125 0 0121 4.875V9.35" /></svg>
                        <span class="admin-nav-label {{ $navLabelVisibilityClass }}">Shops</span>
                    </a>

                    <a href="{{ route('admin.releases.index') }}" title="App Releases" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $navAlignmentClass }} {{ request()->routeIs('admin.releases.*') ? $activeNavClass : $inactiveNavClass }}">
                        <svg class="admin-nav-item-icon {{ $iconSpacingClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        <span class="admin-nav-label {{ $navLabelVisibilityClass }}">App Releases</span>
                    </a>

                    <a href="{{ route('admin.settings.index') }}" title="Settings" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $navAlignmentClass }} {{ request()->routeIs('admin.settings.*') ? $activeNavClass : $inactiveNavClass }}">
                        <svg class="admin-nav-item-icon {{ $iconSpacingClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <span class="admin-nav-label {{ $navLabelVisibilityClass }}">Settings</span>
                    </a>
                </nav>

                <div x-show="isTop" class="ml-2 hidden items-center gap-2 pr-2 sm:flex" x-cloak>
                    <!-- Enhanced Notification Bell for 'isTop' Layout -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" type="button" class="relative p-2 text-gray-400 transition-colors rounded-full hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-800 focus:outline-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @if($admin->unreadNotifications()->count() > 0)
                                <span class="absolute top-1.5 right-1.5 flex h-2 w-2 rounded-full bg-red-500 ring-2 ring-white dark:ring-slate-900"></span>
                            @endif
                        </button>

                        <div x-show="open" @click.away="open = false" style="display: none;" 
                                x-transition:enter="transition ease-out duration-100" 
                                x-transition:enter-start="transform opacity-0 scale-95" 
                                x-transition:enter-end="transform opacity-100 scale-100" 
                                x-transition:leave="transition ease-in duration-75" 
                                x-transition:leave-start="transform opacity-100 scale-100" 
                                x-transition:leave-end="transform opacity-0 scale-95" 
                                class="absolute right-0 w-80 mt-2 origin-top-right bg-white dark:bg-slate-900 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                            
                            <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center bg-gray-50 dark:bg-slate-800/50 rounded-t-md">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Notifications</h3>
                                @if($admin->unreadNotifications()->count() > 0)
                                    <form action="{{ route('admin.notifications.markAllAsRead') }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">Mark all read</button>
                                    </form>
                                @endif
                            </div>
                            
                            <div class="max-h-96 overflow-y-auto">
                                @forelse($admin->unreadNotifications as $notification)
                                    <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-800/80 hover:bg-gray-50 dark:hover:bg-slate-800/30 transition-colors">
                                        <p class="text-sm text-gray-800 dark:text-gray-200">
                                            {{ $notification->data['message'] ?? 'New notification' }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                @empty
                                    <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No new notifications.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="flex h-8 w-8 items-center justify-center rounded-full {{ $theme['avatar_bg'] }}">
                        <span class="text-sm font-medium {{ $theme['avatar_text'] }}">{{ substr($admin->name, 0, 1) }}</span>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}" class="flex items-center">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" title="Logout">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>

                <div x-show="!isTop" class="border-t border-gray-200 px-4 py-4 dark:border-slate-800" x-cloak>
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full {{ $theme['avatar_bg'] }}">
                                <span class="text-sm font-medium {{ $theme['avatar_text'] }}">{{ substr($admin->name, 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="admin-sidebar-user-meta ml-3 min-w-0 flex-1 {{ $navLabelVisibilityClass }}">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-slate-100">{{ $admin->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-slate-400">{{ ucfirst($admin->role) }}</p>
                        </div>
                        
                        <!-- Notification Bell for Sidebar Layout -->
                        <div class="relative mr-2" x-data="{ open: false }">
                            <button @click="open = !open" type="button" class="relative text-gray-400 hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-100 focus:outline-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                @if($admin->unreadNotifications()->count() > 0)
                                    <span class="absolute -top-0.5 -right-0.5 flex h-2 w-2 rounded-full bg-red-500 ring-2 ring-white dark:ring-slate-900"></span>
                                @endif
                            </button>

                            <div x-show="open" @click.away="open = false" style="display: none;" 
                                    x-transition:enter="transition ease-out duration-100" 
                                    x-transition:enter-start="transform opacity-0 scale-95" 
                                    x-transition:enter-end="transform opacity-100 scale-100" 
                                    x-transition:leave="transition ease-in duration-75" 
                                    x-transition:leave-start="transform opacity-100 scale-100" 
                                    x-transition:leave-end="transform opacity-0 scale-95" 
                                    :class="isRight ? '-right-4 origin-bottom-right' : 'left-0 origin-bottom-left'"
                                    class="absolute bottom-full mb-2 w-72 md:w-80 bg-white dark:bg-slate-900 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                                
                                <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center bg-gray-50 dark:bg-slate-800/50 rounded-t-md">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Notifications</h3>
                                    @if($admin->unreadNotifications()->count() > 0)
                                        <form action="{{ route('admin.notifications.markAllAsRead') }}" method="POST" class="m-0">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">Mark all read</button>
                                        </form>
                                    @endif
                                </div>
                                
                                <div class="max-h-80 overflow-y-auto">
                                    @forelse($admin->unreadNotifications as $notification)
                                        <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-800/80 hover:bg-gray-50 dark:hover:bg-slate-800/30 transition-colors">
                                            <p class="text-sm text-gray-800 dark:text-gray-200">
                                                {{ $notification->data['message'] ?? 'New notification' }}
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    @empty
                                        <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No new notifications.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-100" title="Logout">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <div :class="contentWrapperClass" class="transition-[padding] duration-200">
                <div x-show="!isTop" x-cloak :class="topbarWrapperClass">
                    <div :class="topbarSurfaceClass">
                        <div :class="topbarInnerClass">
                            <button x-show="!isTop" x-on:click="sidebarOpen = true" class="-m-2.5 p-2.5 text-gray-700 dark:text-slate-100 lg:hidden">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                            </button>

                            <div :class="topbarTitleClass">
                                <p class="tenant-wordmark tenant-wordmark-topbar">
                                    <span>Laundry</span><span class="tenant-wordmark-accent">Track</span>
                                </p>
                                @isset($header)
                                    <div class="mt-1 min-w-0">
                                        {{ $header }}
                                    </div>
                                @else
                                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-slate-100">Admin Dashboard</p>
                                @endisset
                            </div>

                            <div :class="topbarUserClass">
                                <!-- Notification Bell Dropdown -->
                                <div class="relative" x-data="{ open: false }" :class="isRight ? 'order-last' : ''">
                                    <button @click="open = !open" type="button" class="relative p-2 text-gray-400 transition-colors rounded-full hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-800 focus:outline-none">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        </svg>
                                        @if($admin->unreadNotifications()->count() > 0)
                                            <span class="absolute top-1.5 right-1.5 flex h-2 w-2 rounded-full bg-red-500 ring-2 ring-white dark:ring-slate-900"></span>
                                        @endif
                                    </button>

                                    <!-- Dropdown menu -->
                                    <div x-show="open" @click.away="open = false" style="display: none;" 
                                         x-transition:enter="transition ease-out duration-100" 
                                         x-transition:enter-start="transform opacity-0 scale-95" 
                                         x-transition:enter-end="transform opacity-100 scale-100" 
                                         x-transition:leave="transition ease-in duration-75" 
                                         x-transition:leave-start="transform opacity-100 scale-100" 
                                         x-transition:leave-end="transform opacity-0 scale-95" 
                                         :class="isRight ? 'left-0 origin-top-left' : 'right-0 origin-top-right'"
                                         class="absolute w-80 mt-2 bg-white dark:bg-slate-900 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                                        
                                        <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center bg-gray-50 dark:bg-slate-800/50 rounded-t-md">
                                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Notifications</h3>
                                            @if($admin->unreadNotifications()->count() > 0)
                                                <form action="{{ route('admin.notifications.markAllAsRead') }}" method="POST" class="m-0">
                                                    @csrf
                                                    <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">Mark all read</button>
                                                </form>
                                            @endif
                                        </div>
                                        
                                        <div class="max-h-96 overflow-y-auto">
                                            @forelse($admin->unreadNotifications as $notification)
                                                <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-800/80 hover:bg-gray-50 dark:hover:bg-slate-800/30 transition-colors">
                                                    <p class="text-sm text-gray-800 dark:text-gray-200">
                                                        {{ $notification->data['message'] ?? 'New notification' }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        {{ $notification->created_at->diffForHumans() }}
                                                    </p>
                                                </div>
                                            @empty
                                                <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                                    No new notifications.
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>

                                <div class="flex h-9 w-9 items-center justify-center rounded-full {{ $theme['avatar_bg'] }}">
                                    <span class="text-sm font-medium {{ $theme['avatar_text'] }}">{{ substr($admin->name, 0, 1) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <main class="px-4 pb-6 sm:px-6 lg:px-8">
                    @if (session('success'))
                        <div class="tenant-alert mb-4 border border-green-200 bg-green-50 p-4 text-green-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                            <p class="text-sm font-medium">{{ session('success') }}</p>
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
