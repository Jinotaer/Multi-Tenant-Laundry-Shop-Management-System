<x-tenant-layout>
    <div class="space-y-6">
        <x-tenant-header title="Settings" description="Configure your payment gateway settings." />
        {{-- Settings Navigation Tabs --}}
        <div class="bg-white shadow-sm rounded-2xl dark:bg-slate-800 p-2">
            <nav class="flex gap-2" aria-label="Settings">
                <a href="{{ route('tenant.settings.profile') }}" 
                   class="flex-1 rounded-lg px-4 py-3 text-sm font-medium text-center transition text-gray-600 hover:bg-gray-100 dark:text-slate-400 dark:hover:bg-slate-700">
                    <div class="flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                        <span>Profile</span>
                    </div>
                </a>
                <a href="{{ route('tenant.settings.paymongo') }}" 
                   class="flex-1 rounded-lg px-4 py-3 text-sm font-medium text-center transition tenant-primary-action">
                    <div class="flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                        </svg>
                        <span>Payment Settings</span>
                    </div>
                </a>
            </nav>
        </div>

        {{-- What is PayMongo Section --}}
        <div class="rounded-2xl p-6 border" style="background: linear-gradient(to right, var(--tenant-theme-accent-soft), var(--tenant-theme-accent-soft-strong)); border-color: var(--tenant-theme-accent);">
            <div class="flex items-start gap-4">
                <div class="rounded-full p-3" style="background-color: var(--tenant-theme-accent-soft-strong);">
                    <svg class="h-6 w-6" style="color: var(--tenant-theme-accent);" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100">What is PayMongo?</h3>
                    <p class="mt-2 text-sm text-gray-700 dark:text-slate-300">PayMongo is a payment gateway that allows you to accept online payments from your customers. When you connect your PayMongo account, customer payments will go directly to YOUR bank account.</p>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-slate-300">Accept GCash</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-slate-300">Accept Cards</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-slate-300">Accept GrabPay</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <div class="max-w-3xl space-y-6">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-700 dark:bg-green-900/20">
                <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-700 dark:bg-red-900/20">
                <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
            </div>
        @endif

        <div class="bg-white shadow-sm rounded-2xl dark:bg-slate-800">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-300">PayMongo Configuration</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Configure your PayMongo account to receive online payments from customers.</p>
            </div>

            <form method="POST" action="{{ route('tenant.settings.paymongo.update') }}" class="p-6 space-y-6">
                @csrf
                @method('PATCH')

                <div>
                    <label for="paymongo_secret_key" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                        Secret Key <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1 relative">
                        <input 
                            type="password" 
                            name="paymongo_secret_key" 
                            id="paymongo_secret_key" 
                            value="{{ old('paymongo_secret_key') }}"
                            placeholder="sk_test_xxxxxxxxxx or sk_live_xxxxxxxxxx"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 pr-10"
                        >
                        <button 
                            type="button" 
                            onclick="togglePassword('paymongo_secret_key')"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Starts with <code class="bg-gray-100 dark:bg-slate-600 px-1 rounded">sk_test_</code> or <code class="bg-gray-100 dark:bg-slate-600 px-1 rounded">sk_live_</code></p>
                    @error('paymongo_secret_key')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="paymongo_public_key" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                        Public Key <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="paymongo_public_key" 
                        id="paymongo_public_key" 
                        value="{{ old('paymongo_public_key', tenant()->paymongo_public_key) }}"
                        placeholder="pk_test_xxxxxxxxxx or pk_live_xxxxxxxxxx"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                    >
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Starts with <code class="bg-gray-100 dark:bg-slate-600 px-1 rounded">pk_test_</code> or <code class="bg-gray-100 dark:bg-slate-600 px-1 rounded">pk_live_</code></p>
                    @error('paymongo_public_key')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                @if (tenant()->paymongo_secret_key)
                    <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-700 dark:bg-green-900/20">
                        <div class="flex items-start gap-3">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-green-800 dark:text-green-200">PayMongo Connected</p>
                                <p class="mt-1 text-xs text-green-700 dark:text-green-300">Your PayMongo account is configured. Customer payments will be sent to your account.</p>
                                <p class="mt-2 text-xs text-green-600 dark:text-green-400">
                                    <strong>Note:</strong> If you downgrade to Starter plan, online payments will be disabled but your credentials will be saved for when you upgrade again.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-700 dark:bg-yellow-900/20">
                        <div class="flex items-start gap-3">
                            <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">PayMongo Not Configured</p>
                                <p class="mt-1 text-xs text-yellow-700 dark:text-yellow-300">Configure your PayMongo credentials to enable online payments from customers.</p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="rounded-lg border p-4" style="background-color: var(--tenant-theme-accent-soft); border-color: var(--tenant-theme-accent);">
                    <h4 class="text-sm font-semibold mb-3 flex items-center gap-2" style="color: var(--tenant-theme-accent);">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                        Quick Setup Guide
                    </h4>
                    <div class="space-y-3">
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full text-white flex items-center justify-center text-xs font-bold" style="background-color: var(--tenant-theme-accent);">1</div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-slate-100">Create PayMongo Account (Free)</p>
                                <p class="text-xs text-gray-700 dark:text-slate-300 mt-1">Visit <a href="https://dashboard.paymongo.com/signup" target="_blank" class="underline font-semibold">dashboard.paymongo.com/signup</a> and create your free account</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full text-white flex items-center justify-center text-xs font-bold" style="background-color: var(--tenant-theme-accent);">2</div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-slate-100">Get Your API Keys</p>
                                <p class="text-xs text-gray-700 dark:text-slate-300 mt-1">Go to Developers → API Keys → Copy your Secret Key and Public Key</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full text-white flex items-center justify-center text-xs font-bold" style="background-color: var(--tenant-theme-accent);">3</div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-slate-100">Paste Keys Below</p>
                                <p class="text-xs text-gray-700 dark:text-slate-300 mt-1">Enter your keys in the form below and click Save</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-green-600 text-white flex items-center justify-center text-xs font-bold">✓</div>
                            <div>
                                <p class="text-sm font-medium text-green-900 dark:text-green-100">Start Accepting Payments!</p>
                                <p class="text-xs text-green-700 dark:text-green-300 mt-1">Your customers can now pay online and money goes to your account</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t" style="border-color: var(--tenant-theme-accent);">
                        <p class="text-xs text-gray-800 dark:text-slate-200">
                            <strong>💡 Tip:</strong> Start with Test Keys (sk_test_...) to try it out, then switch to Live Keys (sk_live_...) when ready for real payments.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    @if (tenant()->paymongo_secret_key)
                        <button 
                            type="button"
                            onclick="if(confirm('Remove PayMongo configuration? Online payments will be disabled.')) { document.getElementById('remove-form').submit(); }"
                            class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 dark:border-red-700 dark:bg-slate-800 dark:text-red-400 dark:hover:bg-slate-700"
                        >
                            Remove Configuration
                        </button>
                    @endif
                    <button 
                        type="submit" 
                        class="tenant-primary-action rounded-lg px-4 py-2 text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
                    >
                        Save Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if (tenant()->paymongo_secret_key)
        <form id="remove-form" method="POST" action="{{ route('tenant.settings.paymongo.remove') }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            field.type = field.type === 'password' ? 'text' : 'password';
        }
    </script>
</x-tenant-layout>
