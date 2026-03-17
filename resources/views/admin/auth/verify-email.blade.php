<x-admin-guest-layout>
    <div class="mb-6 text-center">
        <div class="tenant-auth-icon mx-auto mb-4">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 01-.668 1.591l-7.5 7.5a2.25 2.25 0 01-3.182 0l-7.5-7.5A2.25 2.25 0 011.5 9.906V6.75A2.25 2.25 0 013.75 4.5h16.5a2.25 2.25 0 012.25 2.25V9Z" />
            </svg>
        </div>
        <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-slate-100">Verify your email</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Before continuing, confirm your email address from the verification link we sent.</p>
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mb-6 text-sm text-gray-600 dark:text-slate-300">
        {{ __('Thanks for signing up. Before getting started, please verify your email address. If you did not receive the email, we can send another one.') }}
    </div>

    <div class="space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button type="submit" class="tenant-auth-submit">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
                {{ __('Resend Verification Email') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf

            <button type="submit" class="tenant-auth-link text-sm font-medium">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-admin-guest-layout>
