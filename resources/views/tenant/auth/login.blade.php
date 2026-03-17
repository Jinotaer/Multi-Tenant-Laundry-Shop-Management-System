<x-tenant-guest-layout>
    @php
        $shopName = tenant()?->data['shop_name'] ?? 'LaundryTrack';
        $tenantId
    @endphp

    <div class="mb-6 text-center">
        <div class="tenant-auth-icon mx-auto mb-4">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
        </div>
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
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

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2">
                <input id="remember_me" type="checkbox" name="remember" class="tenant-auth-checkbox rounded border-gray-300 shadow-sm">
                <span class="text-sm text-gray-600 dark:text-slate-300">{{ __('Remember me') }}</span>
            </label>

            <a href="{{ route('tenant.register') }}" class="tenant-auth-link text-sm font-medium">
                {{ __('Create an account') }}
            </a>
        </div>

        <div class="pt-1">
            <button type="submit" class="tenant-auth-submit">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                </svg>
                {{ __('Sign In') }}
            </button>
        </div>
    </form>

    <div class="mt-6 flex items-center gap-3">
        <div class="flex-1 border-t border-gray-200 dark:border-slate-800"></div>
        <span class="text-xs uppercase tracking-[0.22em] text-gray-400 dark:text-slate-500">tenant access</span>
        <div class="flex-1 border-t border-gray-200 dark:border-slate-800"></div>
    </div>

    <div class="mt-4 text-center">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 transition-colors duration-150 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Back to homepage
        </a>
    </div>
</x-tenant-guest-layout>

