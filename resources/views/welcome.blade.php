<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'LaundryTrack') }} — Laundry Shop Management Platform</title>
    <meta name="description" content="The all-in-one SaaS platform for laundry businesses. Manage orders, customers, staff and more with your own dedicated dashboard.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white text-gray-900">

    {{-- ========== NAVBAR ========== --}}
    <nav class="fixed top-0 inset-x-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-100" x-data="{ mobileOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Brand --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <svg class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-xl font-bold text-gray-900">Laundry<span class="text-indigo-600">Track</span></span>
                </a>

                {{-- Desktop Nav --}}
                <div class="hidden md:flex items-center gap-8">
                    <a href="#features" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">Features</a>
                    <a href="#pricing" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">Pricing</a>
                    <a href="#how-it-works" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">How It Works</a>
                </div>

                {{-- CTA Buttons --}}
                <div class="hidden md:flex items-center gap-3">
                    <!-- <a href="{{ route('admin.login') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition px-4 py-2">
                        Admin Login
                    </a> -->
                    <a href="{{ route('shop.pricing') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition shadow-sm">
                        Get Started
                    </a>
                </div>

                {{-- Mobile Burger --}}
                <button x-on:click="mobileOpen = !mobileOpen" class="md:hidden p-2 text-gray-600 hover:text-gray-900">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        <path x-show="mobileOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Mobile Menu --}}
            <div x-show="mobileOpen" x-cloak x-transition class="md:hidden pb-4 border-t border-gray-100">
                <div class="flex flex-col gap-2 pt-4">
                    <a href="#features" x-on:click="mobileOpen = false" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">Features</a>
                    <a href="#pricing" x-on:click="mobileOpen = false" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">Pricing</a>
                    <a href="#how-it-works" x-on:click="mobileOpen = false" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">How It Works</a>
                    <hr class="my-2 border-gray-100">
                    <!-- <a href="{{ route('admin.login') }}" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">Admin Login</a> -->
                    <a href="{{ route('shop.pricing') }}" class="mx-3 inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                        Get Started
                    </a>
                </div>
            </div>
        </div>
    </nav>

    {{-- ========== HERO ========== --}}
    <section class="relative pt-36 pb-24 sm:pt-44 sm:pb-32 lg:pt-52 lg:pb-36 overflow-hidden">
        {{-- Background gradient --}}
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-white to-purple-50"></div>
        <div class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-[28rem] h-[28rem] bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-pulse"></div>
        <div class="absolute bottom-0 left-0 translate-y-1/4 -translate-x-1/4 w-[28rem] h-[28rem] bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-pulse"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center gap-2 bg-indigo-50 border border-indigo-100 rounded-full px-4 py-1.5 mb-6">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                </span>
                <span class="text-sm font-medium text-indigo-700">
                    @if($stats['shops'] > 0)
                        {{ $stats['shops'] }} {{ Str::plural('shop', $stats['shops']) }} already onboard
                    @else
                        Now accepting registrations
                    @endif
                </span>
            </div>

            <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black tracking-tight text-gray-900 leading-[1.1]">
                Manage Your Laundry<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-600">Business Smarter</span>
            </h1>

            <p class="mt-8 text-lg sm:text-xl text-gray-500 max-w-2xl mx-auto leading-relaxed">
                The all-in-one SaaS platform for laundry shops. Get your own dedicated dashboard to manage orders, customers, staff, and finances — all in one place.
            </p>

            <div class="mt-12 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('shop.pricing') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-10 py-4 bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-bold rounded-xl hover:from-indigo-700 hover:to-violet-700 transition-all shadow-lg shadow-indigo-300/50 text-base hover:shadow-xl hover:-translate-y-0.5">
                    <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016A3.001 3.001 0 0021 9.349m-18 0V3h18v6.35" />
                    </svg>
                    Get Started Today
                </a>
                <a href="#pricing" class="w-full sm:w-auto inline-flex items-center justify-center px-10 py-4 bg-white text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition-all border border-gray-200 text-base hover:shadow-md hover:-translate-y-0.5">
                    View Pricing Plans
                    <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                    </svg>
                </a>
            </div>
        </div>
    </section>

    {{-- ========== FEATURES ========== --}}
    <section id="features" class="py-20 sm:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Everything Your Laundry Shop Needs</h2>
                <p class="mt-4 text-lg text-gray-600">Powerful tools built specifically for laundry businesses, from solo operators to multi-branch enterprises.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                {{-- Feature 1 --}}
                <div class="group relative bg-gray-50 rounded-2xl p-8 hover:bg-indigo-50 hover:shadow-lg hover:shadow-indigo-100/50 transition-all duration-300">
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mb-5 group-hover:bg-indigo-200 transition">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Order Management</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Track every order from drop-off to pickup. Real-time status updates, receipts, and order history at your fingertips.</p>
                </div>

                {{-- Feature 2 --}}
                <div class="group relative bg-gray-50 rounded-2xl p-8 hover:bg-indigo-50 hover:shadow-lg hover:shadow-indigo-100/50 transition-all duration-300">
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mb-5 group-hover:bg-indigo-200 transition">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Customer Management</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Build customer profiles, track preferences, and maintain loyalty programs to keep customers coming back.</p>
                </div>

                {{-- Feature 3 --}}
                <div class="group relative bg-gray-50 rounded-2xl p-8 hover:bg-indigo-50 hover:shadow-lg hover:shadow-indigo-100/50 transition-all duration-300">
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mb-5 group-hover:bg-indigo-200 transition">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Pricing & Billing</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Set flexible pricing per service type, weight, or piece. Automated invoicing and payment tracking built in.</p>
                </div>

                {{-- Feature 4 --}}
                <div class="group relative bg-gray-50 rounded-2xl p-8 hover:bg-indigo-50 hover:shadow-lg hover:shadow-indigo-100/50 transition-all duration-300">
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mb-5 group-hover:bg-indigo-200 transition">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Staff Management</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Manage staff accounts with role-based access. Track who handled what, when, and maintain accountability.</p>
                </div>

                {{-- Feature 5 --}}
                <div class="group relative bg-gray-50 rounded-2xl p-8 hover:bg-indigo-50 hover:shadow-lg hover:shadow-indigo-100/50 transition-all duration-300">
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mb-5 group-hover:bg-indigo-200 transition">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Reports & Analytics</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Gain insights into revenue, popular services, peak hours, and more with beautiful, actionable dashboards.</p>
                </div>

                {{-- Feature 6 --}}
                <div class="group relative bg-gray-50 rounded-2xl p-8 hover:bg-indigo-50 hover:shadow-lg hover:shadow-indigo-100/50 transition-all duration-300">
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mb-5 group-hover:bg-indigo-200 transition">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Isolated & Secure</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Each shop gets its own isolated database and subdomain. Your data is completely separate and secure from other shops.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ========== HOW IT WORKS ========== --}}
    <section id="how-it-works" class="py-20 sm:py-28 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Get Started in 4 Easy Steps</h2>
                <p class="mt-4 text-lg text-gray-600">From choosing a plan to running your laundry business — we make it simple.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-10">
                {{-- Step 1 --}}
                <div class="relative text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-600 to-violet-600 text-white rounded-2xl flex items-center justify-center mx-auto mb-6 text-2xl font-bold shadow-lg shadow-indigo-200">
                        1
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Choose a Plan</h3>
                    <p class="text-gray-600 leading-relaxed">Pick the plan that best fits your business needs and scale.</p>
                </div>

                {{-- Step 2 --}}
                <div class="relative text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-600 to-violet-600 text-white rounded-2xl flex items-center justify-center mx-auto mb-6 text-2xl font-bold shadow-lg shadow-indigo-200">
                        2
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Register Your Shop</h3>
                    <p class="text-gray-600 leading-relaxed">Fill out a quick registration form with your shop and owner details.</p>
                </div>

                {{-- Step 3 --}}
                <div class="relative text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-600 to-violet-600 text-white rounded-2xl flex items-center justify-center mx-auto mb-6 text-2xl font-bold shadow-lg shadow-indigo-200">
                        3
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Get Approved</h3>
                    <p class="text-gray-600 leading-relaxed">Our team reviews your application. Once approved, you can start managing your laundry operations.</p>
                </div>

                {{-- Step 4 --}}
                <div class="relative text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-600 to-violet-600 text-white rounded-2xl flex items-center justify-center mx-auto mb-6 text-2xl font-bold shadow-lg shadow-indigo-200">
                        4
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Start Managing</h3>
                    <p class="text-gray-600 leading-relaxed">Log in to your dedicated dashboard and start managing orders, customers, and staff right away.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ========== PRICING ========== --}}
    <section id="pricing" class="py-20 sm:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Simple, Transparent Pricing</h2>
                <p class="mt-4 text-lg text-gray-600">Start free, upgrade when you're ready. No hidden fees, no surprises.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-{{ min(count($plans), 3) }} gap-8 max-w-5xl mx-auto">
                @foreach($plans as $plan)
                    <div class="relative flex flex-col bg-white rounded-2xl border-2 {{ !$plan->isFree() ? 'border-indigo-600 shadow-xl shadow-indigo-100' : 'border-gray-200' }} overflow-hidden">
                        {{-- Popular badge --}}
                        @if(!$plan->isFree())
                            <div class="absolute top-0 right-0">
                                <div class="bg-indigo-600 text-white text-xs font-bold px-3 py-1 rounded-bl-lg">
                                    POPULAR
                                </div>
                            </div>
                        @endif

                        <div class="p-8 flex-1">
                            <h3 class="text-xl font-bold text-gray-900">{{ $plan->name }}</h3>
                            <p class="mt-2 text-sm text-gray-500">{{ $plan->description }}</p>

                            <div class="mt-6 mb-8">
                                @if($plan->isFree())
                                    <span class="text-5xl font-extrabold text-gray-900">Free</span>
                                @else
                                    <span class="text-5xl font-extrabold text-gray-900">₱{{ number_format($plan->price, 0) }}</span>
                                    <span class="text-gray-500 text-base font-medium">/{{ $plan->billing_cycle }}</span>
                                @endif
                            </div>

                            {{-- Limits --}}
                            <div class="space-y-3 mb-8">
                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5 text-indigo-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-sm text-gray-700"><strong>{{ $plan->staff_limit_display }}</strong> {{ Str::plural('staff account', $plan->staff_limit ?: 99) }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5 text-indigo-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-sm text-gray-700"><strong>{{ $plan->customer_limit_display }}</strong> customers</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5 text-indigo-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-sm text-gray-700"><strong>{{ $plan->order_limit_display }}</strong> orders/month</span>
                                </div>
                            </div>

                            {{-- Features list --}}
                            <div class="border-t border-gray-100 pt-6">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Features included</p>
                                <ul class="space-y-2.5">
                                    @php
                                        $allFeatures = config('themes.features');
                                        $planFeatures = $plan->features ?? [];
                                    @endphp
                                    @foreach($allFeatures as $featureKey => $featureConfig)
                                        <li class="flex items-center gap-2.5">
                                            @if(in_array($featureKey, $planFeatures))
                                                <svg class="h-4 w-4 text-indigo-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                                <span class="text-sm text-gray-700">{{ $featureConfig['label'] }}</span>
                                            @else
                                                <svg class="h-4 w-4 text-gray-300 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                <span class="text-sm text-gray-400">{{ $featureConfig['label'] }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        {{-- CTA --}}
                        <div class="p-8 pt-0">
                            <a href="{{ route('shop.register', ['plan' => $plan->id]) }}" class="block w-full text-center px-6 py-3 rounded-xl font-semibold text-sm transition {{ !$plan->isFree() ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg shadow-indigo-200' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }}">
                                {{ $plan->name === 'Basic' ? 'Get Started — ' . $plan->name : 'Subscribe to ' . $plan->name }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ========== FINAL CTA ========== --}}
    <section class="py-20 sm:py-28 bg-gradient-to-br from-indigo-600 to-purple-700 relative overflow-hidden">
        <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-white">Ready to Modernize Your Laundry Business?</h2>
            <p class="mt-4 text-lg text-indigo-100 max-w-2xl mx-auto">Join the growing number of laundry shop owners who trust LaundryTrack to run their operations efficiently.</p>
            <div class="mt-10">
                <a href="{{ route('shop.pricing') }}" class="inline-flex items-center px-8 py-4 bg-white text-indigo-700 font-bold rounded-xl hover:bg-indigo-50 transition shadow-xl text-base">
                    Get Started Now
                    <svg class="ml-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>
        </div>
    </section>

    {{-- ========== FOOTER ========== --}}
    <footer class="bg-gray-900 text-gray-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-2">
                    <svg class="h-6 w-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-lg font-bold text-white">Laundry<span class="text-indigo-400">Track</span></span>
                </div>

                <div class="flex items-center gap-6 text-sm">
                    <a href="{{ route('shop.pricing') }}" class="hover:text-white transition">Pricing</a>
                    <a href="{{ route('admin.login') }}" class="hover:text-white transition">Admin Portal</a>
                    <a href="#features" class="hover:text-white transition">Features</a>
                    <a href="#pricing" class="hover:text-white transition">Pricing</a>
                </div>

                <p class="text-sm">&copy; {{ date('Y') }} {{ config('app.name', 'LaundryTrack') }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

</body>
</html>
