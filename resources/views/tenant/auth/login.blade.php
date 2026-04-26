<x-tenant-guest-layout>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @php
        $shopName = tenant()?->data['shop_name'] ?? 'LaundryTrack';
    @endphp

    <div class="mb-6 text-center">
        <x-application-logo class="mx-auto mb-2 h-24 w-24 object-cover rounded-full drop-shadow-sm border border-gray-200 dark:border-slate-700" />
        <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-slate-100">Sign in to {{ $shopName }}</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Use your owner, staff, or customer account to access the workspace.</p>
    </div>

    <x-auth-session-status class="mb-4 text-sm text-green-600 dark:text-emerald-300" :status="session('status')" />

    <form method="POST" action="{{ route('tenant.login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email address')" />
            <div class="relative mt-1.5">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 dark:text-slate-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                </div>
                <x-text-input
                    id="email"
                    type="email"
                    name="email"
                    :value="old('email')"
                    required
                    autofocus
                    autocomplete="username"
                    class="block w-full pl-10"
                    placeholder="owner@example.com"
                />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <div class="relative mt-1.5">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 dark:text-slate-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25z" />
                    </svg>
                </div>
                <x-text-input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="block w-full pl-10"
                    placeholder="Enter your password"
                />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-4">
            <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2">
                <input id="remember_me" type="checkbox" name="remember" class="tenant-auth-checkbox rounded border-gray-300 shadow-sm">
                <span class="text-sm text-gray-600 dark:text-slate-300">{{ __('Remember me') }}</span>
            </label>

            <a href="{{ route('tenant.password.request') }}" class="tenant-auth-link text-sm font-medium">
                {{ __('Forgot password?') }}
            </a>
        </div>

        <div class="mt-4 flex flex-col items-center">
            <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
            <x-input-error :messages="$errors->get('g-recaptcha-response')" class="mt-2 text-center" />
        </div>

        <div class="pt-1">
            <button type="submit" class="tenant-auth-submit">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                </svg>
                {{ __('Sign In') }}
            </button>
        </div>
    </form>

    <div class="mt-4 text-center">
        <span class="text-sm text-gray-600 dark:text-slate-400">Don't have an account? </span>
        <a href="{{ route('tenant.register') }}" class="tenant-auth-link text-sm font-medium">
            {{ __('Create an account') }}
        </a>
    </div>
</x-tenant-guest-layout>
