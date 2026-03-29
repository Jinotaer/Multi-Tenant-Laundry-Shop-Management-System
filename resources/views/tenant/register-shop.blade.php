<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Register Shop - {{ config('app.name', 'LaundryTrack') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white text-gray-900">

    {{-- ========== NAVBAR ========== --}}
    <nav class="absolute top-0 inset-x-0 z-50 bg-white border-b border-gray-100/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Brand --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                    <svg class="h-8 w-8 text-indigo-600 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <rect x="5" y="3" width="14" height="18" rx="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14"/>
                        <circle cx="12" cy="14" r="4" stroke-linecap="round" stroke-linejoin="round"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5.5h.01M12 5.5h.01M15 5.5h.01"/>
                    </svg>
                    <span class="text-xl font-bold text-gray-900">Laundry<span class="text-indigo-600">Track</span></span>
                </a>
                
                <a href="{{ route('home') }}" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Home
                </a>
            </div>
        </div>
    </nav>

    <main class="relative min-h-screen flex flex-col justify-center py-24 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-white to-purple-50"></div>
        <div class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-[28rem] h-[28rem] bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-pulse"></div>
        <div class="absolute bottom-0 left-0 translate-y-1/4 -translate-x-1/4 w-[28rem] h-[28rem] bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-pulse"></div>
        
        <div class="relative w-full max-w-lg mx-auto px-4 z-10">
            <div class="bg-white/80 backdrop-blur-xl shadow-2xl shadow-indigo-100/60 rounded-3xl border border-gray-200 p-8 sm:p-10">
                
                <div class="mb-8 text-center">
                    <h2 class="text-3xl font-black tracking-tight text-gray-900">Register Your Shop</h2>
                    <p class="text-sm text-gray-500 mt-2">Create your <span class="font-semibold text-indigo-600">LaundryTrack</span> account</p>
                </div>

                @if($selectedPlan)
                    <div class="mb-8 p-4 rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50/50 to-white shadow-sm relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-100 rounded-full opacity-50 blur-2xl group-hover:scale-110 transition-transform duration-500"></div>
                        
                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-base font-bold text-gray-900">{{ $selectedPlan->name }} Plan</span>
                                <span class="text-base font-black {{ $selectedPlan->isFree() ? 'text-green-600' : 'text-indigo-600' }}">{{ $selectedPlan->formatted_price }}</span>
                            </div>
                            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-600 font-medium mb-3">
                                <span class="flex items-center"><svg class="w-3.5 h-3.5 mr-1 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>{{ $selectedPlan->staff_limit_display }} staff</span>
                                <span class="flex items-center"><svg class="w-3.5 h-3.5 mr-1 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>{{ $selectedPlan->customer_limit_display }} customers</span>
                            </div>
                            <div class="flex items-center justify-between pt-3 border-t border-indigo-100/50">
                                <span class="text-xs text-gray-500 font-medium">Selected details</span>
                                <a href="{{ route('shop.pricing') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold transition-colors">Change plan &rarr;</a>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mb-8 p-4 rounded-2xl border border-gray-200 bg-gray-50 flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500">No plan selected.</span>
                        <a href="{{ route('shop.pricing') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-bold transition-colors">Choose plan &rarr;</a>
                    </div>
                @endif

                <form method="POST" action="{{ route('shop.register') }}">
                    @csrf

                    @if($selectedPlan)
                        <input type="hidden" name="subscription_plan_id" value="{{ $selectedPlan->id }}">
                    @endif

                    <div class="space-y-5">
                        <div>
                            <x-input-label for="shop_name" :value="__('Shop Name')" class="text-gray-700 font-semibold" />
                            <x-text-input id="shop_name" class="block mt-1 w-full bg-gray-50/50 border-gray-200 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 rounded-xl transition-all" type="text" name="shop_name" :value="old('shop_name')" required autofocus placeholder="e.g. Fresh & Clean" />
                            <x-input-error :messages="$errors->get('shop_name')" class="mt-2" />
                        </div>

                        <div class="relative py-2">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200"></div>
                            </div>
                            <div class="relative flex justify-center">
                                <span class="bg-white/80 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Account Details</span>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="name" :value="__('Full Name')" class="text-gray-700 font-semibold"/>
                            <x-text-input id="name" class="block mt-1 w-full bg-gray-50/50 border-gray-200 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 rounded-xl transition-all" type="text" name="name" :value="old('name')" required autocomplete="name" placeholder="John Doe" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Email Address')" class="text-gray-700 font-semibold"/>
                            <x-text-input id="email" class="block mt-1 w-full bg-gray-50/50 border-gray-200 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 rounded-xl transition-all" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="john@example.com" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="password" :value="__('Password')" class="text-gray-700 font-semibold" />
                            <x-text-input id="password" class="block mt-1 w-full bg-gray-50/50 border-gray-200 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 rounded-xl transition-all" type="password" name="password" required autocomplete="new-password" placeholder="" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-gray-700 font-semibold"/>
                            <x-text-input id="password_confirmation" class="block mt-1 w-full bg-gray-50/50 border-gray-200 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 rounded-xl transition-all" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="" />
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-8">
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-3.5 bg-gradient-to-r from-indigo-600 to-violet-600 border border-transparent rounded-xl font-bold text-white uppercase tracking-widest hover:from-indigo-700 hover:to-violet-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-300 shadow-lg shadow-indigo-300/50 hover:shadow-xl hover:-translate-y-0.5">
                            {{ __('Complete Registration') }}
                            <svg class="ml-2 -mr-1 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

</body>
</html>
