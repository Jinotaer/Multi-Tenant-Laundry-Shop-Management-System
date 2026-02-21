<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    @php $theme = app(\App\Services\ThemeService::class)->getAdminTheme(); @endphp

    <!-- Settings Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <a href="{{ route('admin.settings.profile') }}" class="border-b-2 px-1 pb-3 text-sm font-medium {{ request()->routeIs('admin.settings.profile*') || request()->routeIs('admin.settings.password') ? 'border-current ' . $theme['nav_active_text'] : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                Profile
            </a>
            <a href="{{ route('admin.settings.theme') }}" class="border-b-2 px-1 pb-3 text-sm font-medium {{ request()->routeIs('admin.settings.theme*') || request()->routeIs('admin.settings.logo*') ? 'border-current ' . $theme['nav_active_text'] : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                Theme
            </a>
        </nav>
    </div>

    <div class="space-y-6">
        <!-- Logo Upload -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Logo</h3>
                <p class="text-sm text-gray-500 mb-6">Upload a logo for the admin panel sidebar.</p>

                <div class="flex items-start gap-6">
                    <!-- Current Logo Preview -->
                    <div class="flex-shrink-0">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Admin Logo" class="h-16 w-16 rounded-lg object-contain border border-gray-200 bg-white p-1">
                        @else
                            <div class="h-16 w-16 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center">
                                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" /></svg>
                            </div>
                        @endif
                    </div>

                    <div class="flex-1">
                        <form method="POST" action="{{ route('admin.settings.logo') }}" enctype="multipart/form-data">
                            @csrf

                            <div>
                                <input type="file" name="logo" accept="image/jpeg,image/png,image/svg+xml" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                                <p class="mt-1 text-xs text-gray-400">JPG, PNG or SVG. Max 2MB.</p>
                            </div>

                            @error('logo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            <div class="mt-3 flex items-center gap-3">
                                <button type="submit" class="inline-flex items-center px-4 py-2 {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition ease-in-out duration-150">
                                    {{ __('Upload Logo') }}
                                </button>

                                @if ($logoUrl)
                                    </form>
                                    <form method="POST" action="{{ route('admin.settings.logo.remove') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition ease-in-out duration-150" onclick="return confirm('Remove the logo?')">
                                            {{ __('Remove') }}
                                        </button>
                                    </form>
                                @else
                                    </form>
                                @endif
                            </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Theme Selection -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Theme</h3>
                <p class="text-sm text-gray-500 mb-6">Choose a color theme for your admin panel.</p>

                <form method="POST" action="{{ route('admin.settings.theme.update') }}">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4">
                        @foreach ($presets as $key => $preset)
                            <label
                                class="relative flex flex-col items-center p-4 border-2 rounded-lg cursor-pointer transition-all hover:shadow-md" style="{{ $currentTheme === $key ? 'border-color: ' . $preset['preview'] . '; box-shadow: 0 0 0 2px ' . $preset['preview'] . '33;' : 'border-color: #e5e7eb;' }}"
                            >
                                <input type="radio" name="theme" value="{{ $key }}" class="sr-only" {{ $currentTheme === $key ? 'checked' : '' }}>

                                <!-- Color Preview -->
                                <div class="w-12 h-12 rounded-full mb-3 shadow-inner" style="background-color: {{ $preset['preview'] }}"></div>

                                <!-- Label -->
                                <span class="text-sm font-medium text-gray-700">{{ $preset['label'] }}</span>

                                <!-- Check indicator -->
                                @if ($currentTheme === $key)
                                    <div class="absolute top-2 right-2">
                                        <svg class="w-5 h-5 {{ $theme['nav_active_text'] }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                    </div>
                                @endif
                            </label>
                        @endforeach
                    </div>

                    @error('theme')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="mt-6">
                        <button type="submit" class="inline-flex items-center px-4 py-2 {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition ease-in-out duration-150">
                            {{ __('Save Theme') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
