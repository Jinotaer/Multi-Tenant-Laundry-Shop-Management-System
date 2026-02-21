<x-guest-layout>
    <div class="mb-4 text-center">
        <h2 class="text-lg font-semibold text-gray-700">Register Your Laundry Shop</h2>
        <p class="text-sm text-gray-500">Submit your application to get started on LaundryTrack</p>
    </div>

    {{-- Selected Plan Summary --}}
    @if($selectedPlan)
        <div class="mb-6 p-4 rounded-xl border-2 border-indigo-200 bg-indigo-50/50">
            <div class="flex items-center justify-between mb-1">
                <span class="text-sm font-bold text-gray-900">{{ $selectedPlan->name }} Plan</span>
                <span class="text-sm font-extrabold {{ $selectedPlan->isFree() ? 'text-green-600' : 'text-indigo-600' }}">{{ $selectedPlan->formatted_price }}</span>
            </div>
            <div class="flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-gray-500">
                <span>{{ $selectedPlan->staff_limit_display }} staff</span>
                <span>{{ $selectedPlan->customer_limit_display }} customers</span>
                <span>{{ $selectedPlan->order_limit_display }} orders/mo</span>
            </div>
            <div class="mt-2 flex items-center justify-between">
                <span class="text-xs text-green-600 font-medium">Includes 30-day free trial</span>
                <a href="{{ route('shop.pricing') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium underline">Change plan</a>
            </div>
        </div>
    @else
        <div class="mb-6 p-4 rounded-xl border border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500">No plan selected — default plan will be assigned.</span>
                <a href="{{ route('shop.pricing') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium underline">Choose a plan</a>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('shop.register') }}">
        @csrf

        {{-- Pass plan ID as hidden field --}}
        @if($selectedPlan)
            <input type="hidden" name="subscription_plan_id" value="{{ $selectedPlan->id }}">
        @endif

        <!-- Shop Name -->
        <div>
            <x-input-label for="shop_name" :value="__('Shop Name')" />
            <x-text-input id="shop_name" class="block mt-1 w-full" type="text" name="shop_name" :value="old('shop_name')" required autofocus placeholder="e.g. Clean & Fresh Laundry" />
            <x-input-error :messages="$errors->get('shop_name')" class="mt-2" />
        </div>

        <hr class="my-6">

        <p class="text-sm font-medium text-gray-700 mb-4">Owner Account</p>

        <!-- Owner Name -->
        <div>
            <x-input-label for="name" :value="__('Your Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full justify-center">
                {{ __('Submit Registration') }}
            </x-primary-button>
        </div>

        <div class="mt-4 text-center">
            <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('admin.login') }}">
                {{ __('Admin? Log in here') }}
            </a>
        </div>
    </form>
</x-guest-layout>
