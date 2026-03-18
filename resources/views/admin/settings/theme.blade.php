<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Layout') }}
        </h2>
    </x-slot>

    @php
        $theme = app(\App\Services\ThemeService::class)->getAdminTheme();
        $settingSections = [
            'sidebar_position' => [
                'label' => 'Sidebar Position',
                'description' => 'Choose where the main navigation appears in the admin shell.',
            ],
            'topbar_behavior' => [
                'label' => 'Topbar Behavior',
                'description' => 'Control whether the topbar stays visible while content scrolls.',
            ],
            'topbar_style' => [
                'label' => 'Topbar Style',
                'description' => 'Choose how the header surface should look across the admin shell.',
            ],
            'sidebar_style' => [
                'label' => 'Sidebar Style',
                'description' => 'Pick a full panel, floating shell, or compact icon rail.',
            ],
            'color_mode' => [
                'label' => 'Color Mode',
                'description' => 'Set the admin panel to light, dark, or follow the device theme.',
            ],
            'font_size' => [
                'label' => 'Font Size',
                'description' => 'Scale the interface text density for readability.',
            ],
            'border_radius' => [
                'label' => 'Border Radius',
                'description' => 'Adjust how rounded the cards, panels, and controls look.',
            ],
            'icon_size' => [
                'label' => 'Icon Size',
                'description' => 'Control icon scale in navigation and actions.',
            ],
            'icon_stroke' => [
                'label' => 'Icon Stroke',
                'description' => 'Control icon line thickness across the shell.',
            ],
            'logo_visibility' => [
                'label' => 'Logo Visibility',
                'description' => 'Show or hide the uploaded logo mark in the admin sidebar brand area.',
            ],
        ];
        $formHasErrors = $errors->adminLayoutSettings->any();
        $values = [
            'theme' => $formHasErrors ? old('theme', $resolvedLayout['theme']) : $resolvedLayout['theme'],
            'sidebar_position' => $formHasErrors ? old('sidebar_position', $resolvedLayout['sidebar_position']) : $resolvedLayout['sidebar_position'],
            'topbar_behavior' => $formHasErrors ? old('topbar_behavior', $resolvedLayout['topbar_behavior']) : $resolvedLayout['topbar_behavior'],
            'topbar_style' => $formHasErrors ? old('topbar_style', $resolvedLayout['topbar_style']) : $resolvedLayout['topbar_style'],
            'sidebar_style' => $formHasErrors ? old('sidebar_style', $resolvedLayout['sidebar_style']) : $resolvedLayout['sidebar_style'],
            'color_mode' => $formHasErrors ? old('color_mode', $resolvedLayout['color_mode']) : $resolvedLayout['color_mode'],
            'font_size' => $formHasErrors ? old('font_size', $resolvedLayout['font_size']) : $resolvedLayout['font_size'],
            'border_radius' => $formHasErrors ? old('border_radius', $resolvedLayout['border_radius']) : $resolvedLayout['border_radius'],
            'icon_size' => $formHasErrors ? old('icon_size', $resolvedLayout['icon_size']) : $resolvedLayout['icon_size'],
            'icon_stroke' => $formHasErrors ? old('icon_stroke', $resolvedLayout['icon_stroke']) : $resolvedLayout['icon_stroke'],
            'logo_visibility' => $formHasErrors ? old('logo_visibility', $resolvedLayout['logo_visibility'] ? '1' : '0') : ($resolvedLayout['logo_visibility'] ? '1' : '0'),
        ];
        $widgetOrder = $formHasErrors ? old('dashboard_widget_order', $resolvedLayout['dashboard_widget_order']) : $resolvedLayout['dashboard_widget_order'];
        $mapWidgetItems = function (array $order, array $catalog): array {
            $items = [];

            foreach ($order as $widgetKey) {
                if (! array_key_exists($widgetKey, $catalog)) {
                    continue;
                }

                $items[] = [
                    'key' => $widgetKey,
                    'label' => $catalog[$widgetKey]['label'],
                    'description' => $catalog[$widgetKey]['description'],
                ];
            }

            return $items;
        };
        $widgetItems = $mapWidgetItems($widgetOrder, $widgetCatalog);
        $themePreviewColors = [];

        foreach ($presets as $key => $preset) {
            $themePreviewColors[$key] = $preset['preview'];
        }

        $selectionAccent = $themePreviewColors[(string) $values['theme']] ?? '#6366f1';
        $formSelections = $values;
        unset($formSelections['theme']);

        $formState = [
            'selectedTheme' => (string) $values['theme'],
            'themeColors' => $themePreviewColors,
            'selectedOptions' => $formSelections,
        ];
    @endphp

    <div class="mb-6 border-b border-gray-200 dark:border-slate-800">
        <nav class="-mb-px flex flex-wrap gap-6">
            <a href="{{ route('admin.settings.profile') }}" class="border-b-2 px-1 pb-3 text-sm font-medium {{ request()->routeIs('admin.settings.profile*') || request()->routeIs('admin.settings.password') ? 'border-current ' . $theme['nav_active_text'] : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:text-slate-200' }}">
                Profile
            </a>
            <a href="{{ route('admin.settings.theme') }}" class="border-b-2 px-1 pb-3 text-sm font-medium border-current {{ $theme['nav_active_text'] }}">
                Layout
            </a>
        </nav>
    </div>

    <div class="space-y-8">
        <section class="space-y-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Admin Layout</h3>
                    <p class="text-sm text-gray-500 dark:text-slate-400">Customize the admin shell, theme, and dashboard ordering for this account.</p>
                </div>
                <p class="text-xs uppercase tracking-[0.2em] text-gray-400">Admin controls</p>
            </div>

            @if ($errors->adminLayoutSettings->any())
                <div class="tenant-alert border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
                    {{ $errors->adminLayoutSettings->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.settings.theme.update') }}" class="space-y-6" x-data='@json($formState)' style="--selection-accent: {{ $selectionAccent }}">
                @csrf
                @method('PATCH')

                <div class="tenant-panel overflow-hidden">
                    <div class="border-b border-gray-200 px-6 py-5 dark:border-slate-800">
                        <h4 class="text-base font-semibold text-gray-900 dark:text-slate-100">Theme Color</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Choose the accent palette for active navigation, badges, and action buttons.</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-6">
                            @foreach ($presets as $key => $preset)
                                <label class="tenant-choice-card" :class="selectedTheme === '{{ $key }}' ? 'tenant-choice-card-active' : ''" style="--selection-accent: {{ $preset['preview'] }}">
                                    <input type="radio" name="theme" value="{{ $key }}" class="sr-only" x-model="selectedTheme" {{ $values['theme'] === $key ? 'checked' : '' }}>
                                    <div class="mb-6 flex items-start justify-between gap-3">
                                        <span class="tenant-selection-indicator" :class="selectedTheme === '{{ $key }}' ? 'opacity-100' : 'opacity-0'">Selected</span>
                                        <span class="tenant-selection-check" :class="selectedTheme === '{{ $key }}' ? 'tenant-selection-check-active' : ''">
                                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m7.5 12.75 3 3 6-7.5" />
                                            </svg>
                                        </span>
                                    </div>
                                    <div class="mb-4 h-12 w-12 rounded-full shadow-inner" style="background-color: {{ $preset['preview'] }}"></div>
                                    <p class="text-base font-semibold text-gray-900 dark:text-slate-100">{{ $preset['label'] }}</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">{{ $preset['preview'] }}</p>
                                </label>
                            @endforeach
                        </div>

                        @error('theme', 'adminLayoutSettings')
                            <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-6 xl:grid-cols-2">
                    @foreach ($settingSections as $field => $section)
                        <section class="tenant-panel overflow-hidden">
                            <div class="border-b border-gray-200 px-6 py-5 dark:border-slate-800">
                                <h4 class="text-base font-semibold text-gray-900 dark:text-slate-100">{{ $section['label'] }}</h4>
                                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ $section['description'] }}</p>
                            </div>
                            <div class="grid gap-3 p-6 md:grid-cols-2">
                                @foreach ($optionGroups[$field] as $value => $option)
                                    <label class="tenant-choice-card" :class="selectedOptions.{{ $field }} === '{{ (string) $value }}' ? 'tenant-choice-card-active' : ''">
                                        <input type="radio" name="{{ $field }}" value="{{ $value }}" class="sr-only" x-model="selectedOptions.{{ $field }}" {{ (string) $values[$field] === (string) $value ? 'checked' : '' }}>
                                        <div class="mb-6 flex items-start justify-between gap-3">
                                            <span class="tenant-selection-indicator" :class="selectedOptions.{{ $field }} === '{{ (string) $value }}' ? 'opacity-100' : 'opacity-0'">Selected</span>
                                            <span class="tenant-selection-check" :class="selectedOptions.{{ $field }} === '{{ (string) $value }}' ? 'tenant-selection-check-active' : ''">
                                                <svg fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m7.5 12.75 3 3 6-7.5" />
                                                </svg>
                                            </span>
                                        </div>
                                        <p class="text-lg font-semibold text-gray-900 dark:text-slate-100">{{ $option['label'] }}</p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ $option['description'] }}</p>
                                    </label>
                                @endforeach
                            </div>

                            @error($field, 'adminLayoutSettings')
                                <p class="px-6 pb-5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </section>
                    @endforeach
                </div>

                <section class="tenant-panel overflow-hidden">
                    <div class="border-b border-gray-200 px-6 py-5 dark:border-slate-800">
                        <h4 class="text-base font-semibold text-gray-900 dark:text-slate-100">Dashboard Widget Order</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Drag on desktop or use the move buttons on mobile to set the admin dashboard sequence.</p>
                    </div>
                    <div class="p-6">
                        <div
                            x-data="{
                                items: @js($widgetItems),
                                dragging: null,
                                dragStart(index) {
                                    this.dragging = index;
                                },
                                drop(index) {
                                    if (this.dragging === null || this.dragging === index) {
                                        this.dragging = null;
                                        return;
                                    }

                                    const movedItem = this.items.splice(this.dragging, 1)[0];
                                    this.items.splice(index, 0, movedItem);
                                    this.dragging = null;
                                },
                                moveUp(index) {
                                    if (index === 0) {
                                        return;
                                    }

                                    [this.items[index - 1], this.items[index]] = [this.items[index], this.items[index - 1]];
                                },
                                moveDown(index) {
                                    if (index >= this.items.length - 1) {
                                        return;
                                    }

                                    [this.items[index], this.items[index + 1]] = [this.items[index + 1], this.items[index]];
                                }
                            }"
                            class="space-y-3"
                        >
                            <template x-for="(item, index) in items" :key="item.key">
                                <div
                                    class="tenant-widget-item flex items-center gap-3 border border-gray-200 bg-white px-4 py-4 transition dark:border-slate-800 dark:bg-slate-900"
                                    :class="dragging === index ? 'opacity-70 shadow-lg' : ''"
                                    draggable="true"
                                    @dragstart="dragStart(index)"
                                    @dragend="dragging = null"
                                    @dragover.prevent
                                    @drop.prevent="drop(index)"
                                >
                                    <input type="hidden" name="dashboard_widget_order[]" :value="item.key">
                                    <div class="hidden cursor-move text-gray-400 lg:block">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25h7.5" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-slate-100" x-text="item.label"></p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400" x-text="item.description"></p>
                                    </div>
                                    <div class="flex items-center gap-2 lg:hidden">
                                        <button type="button" class="tenant-icon-button border border-gray-200 bg-white p-2 text-gray-600 transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800" @click="moveUp(index)" :disabled="index === 0">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m15 11.25-3-3m0 0-3 3m3-3v7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                        </button>
                                        <button type="button" class="tenant-icon-button border border-gray-200 bg-white p-2 text-gray-600 transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800" @click="moveDown(index)" :disabled="index === items.length - 1">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m9 12.75 3 3m0 0 3-3m-3 3v-7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        @error('dashboard_widget_order', 'adminLayoutSettings')
                            <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                <div class="flex justify-end">
                    <button type="submit" class="tenant-button inline-flex items-center border border-transparent px-4 py-2 font-semibold text-xs uppercase tracking-widest text-white transition {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }}">
                        Save Layout
                    </button>
                </div>
            </form>

            <div class="tenant-panel overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-5 dark:border-slate-800">
                    <h4 class="text-base font-semibold text-gray-900 dark:text-slate-100">Admin Logo</h4>
                    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Upload a logo for the admin sidebar brand area. Layout visibility is controlled separately above.</p>
                </div>
                <div class="p-6">
                    <div class="flex flex-col gap-6 md:flex-row md:items-start">
                        <div class="flex h-20 w-20 items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-gray-50 dark:border-slate-700 dark:bg-slate-900">
                            @if ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Admin Logo" class="h-16 w-16 object-contain">
                            @else
                                <svg class="h-8 w-8 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                                </svg>
                            @endif
                        </div>

                        <div class="flex-1 space-y-4">
                            <form method="POST" action="{{ route('admin.settings.logo') }}" enctype="multipart/form-data" class="space-y-4">
                                @csrf

                                <div>
                                    <input type="file" name="logo" accept="image/jpeg,image/png,image/svg+xml" class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200 dark:file:bg-slate-800 dark:file:text-slate-100 dark:hover:file:bg-slate-700">
                                    <p class="mt-2 text-xs text-gray-400 dark:text-slate-500">JPG, PNG, or SVG up to 2MB.</p>
                                </div>

                                @error('logo')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="submit" class="tenant-button inline-flex items-center border border-transparent px-4 py-2 font-semibold text-xs uppercase tracking-widest text-white transition {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }}">
                                        Upload Logo
                                    </button>
                                </div>
                            </form>

                            @if ($logoUrl)
                                <form method="POST" action="{{ route('admin.settings.logo.remove') }}">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="tenant-button inline-flex items-center border border-gray-300 bg-white px-4 py-2 font-semibold text-xs uppercase tracking-widest text-gray-700 transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800">
                                        Remove Logo
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-admin-layout>
