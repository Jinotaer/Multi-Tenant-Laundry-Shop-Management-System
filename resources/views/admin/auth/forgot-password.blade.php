<x-admin-guest-layout>
    <div class="mb-6 text-center">
        <div class="tenant-auth-icon mx-auto mb-4">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 8.25v-.75A2.25 2.25 0 0019.5 5.25h-15A2.25 2.25 0 002.25 7.5v9A2.25 2.25 0 004.5 18.75h15a2.25 2.25 0 002.25-2.25v-.75m-19.5 0h19.5m-19.5 0 3.75-3.75m15.75 3.75-3.75-3.75" />
            </svg>
        </div>
        <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-slate-100">Reset admin password</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Enter your admin email and we&apos;ll send you a secure password reset link.</p>
    </div>

    <x-auth-session-status class="mb-4 text-sm text-emerald-600 dark:text-emerald-300" :status="session('status')" />

    <form method="POST" action="{{ route('admin.password.email') }}" class="space-y-4">
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
                    placeholder="admin@example.com"
                />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="pt-1">
            <button type="submit" class="tenant-auth-submit">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
                {{ __('Email Password Reset Link') }}
            </button>
        </div>
    </form>

    <div class="mt-6 flex items-center gap-3">
        <div class="flex-1 border-t border-gray-200 dark:border-slate-800"></div>
        <span class="text-xs uppercase tracking-[0.22em] text-gray-400 dark:text-slate-500">password recovery</span>
        <div class="flex-1 border-t border-gray-200 dark:border-slate-800"></div>
    </div>

    <div class="mt-4 text-center">
        <a href="{{ route('admin.login') }}" class="tenant-auth-link inline-flex items-center gap-1.5 text-sm font-medium">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Back to sign in
        </a>
    </div>
</x-admin-guest-layout>
