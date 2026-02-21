<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Customer</h2>
    </x-slot>

    @php $theme = tenant()->getThemePreset(); @endphp

    <div class="max-w-2xl">
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="p-6">
                @if(session('generated_password'))
                    @php $gp = session('generated_password'); @endphp
                    <div class="mb-4 rounded-md bg-yellow-50 p-4">
                        <p class="text-sm text-yellow-800">Customer account created:</p>
                        <p class="mt-1 text-sm text-gray-900">Email: <strong>{{ $gp['email'] }}</strong></p>
                        <p class="mt-1 text-sm text-gray-900">Password: <strong>{{ $gp['password'] }}</strong></p>
                        <p class="mt-1 text-xs text-gray-600">Please copy the password — it won't be shown again.</p>
                    </div>
                @endif
                <form method="POST" action="{{ route('tenant.customers.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-300 @enderror">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('phone') border-red-300 @enderror">
                        @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('email') border-red-300 @enderror">
                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Account Password (optional)</label>
                        <div class="flex gap-2">
                            <input type="text" name="password" id="password_input" value="{{ old('password', $defaultPassword ?? '') }}"
                                class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('password') border-red-300 @enderror">
                            <button type="button" id="generate_password"
                                class="inline-flex items-center rounded-md border px-3 py-2 text-sm text-gray-700 bg-white hover:bg-gray-50">Generate</button>
                        </div>
                        <p class="mt-1 text-xs text-gray-600">A login will be created for this email using this password if the email is provided.</p>
                        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <!-- <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <textarea name="address" rows="2"
                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('address') border-red-300 @enderror">{{ old('address') }}</textarea>
                        @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div> -->

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" rows="3"
                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('notes') border-red-300 @enderror">{{ old('notes') }}</textarea>
                        @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                            class="inline-flex items-center rounded-md {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-5 py-2 text-sm font-medium text-white shadow-sm transition">
                            Save Customer
                        </button>
                        <a href="{{ route('tenant.customers.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-tenant-layout>

@push('scripts')
<script>
    (function(){
        const btn = document.getElementById('generate_password');
        const input = document.getElementById('password_input');
        if (!btn || !input) return;

        function randomPassword(length = 8) {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
            let out = '';
            for (let i = 0; i < length; i++) {
                out += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return out;
        }

        btn.addEventListener('click', function(){
            input.value = randomPassword(8);
            input.focus();
            input.select();
        });
    })();
</script>
@endpush
