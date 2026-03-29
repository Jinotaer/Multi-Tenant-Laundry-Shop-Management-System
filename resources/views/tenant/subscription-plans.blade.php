<x-tenant-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Available Plans') }}
            </h2>
        </div>
    </x-slot>

    @php $theme = tenant()->getThemePreset(); @endphp

    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-center">
                <h3 class="text-2xl font-bold text-gray-900">Choose the Right Plan for Your Business</h3>
                <p class="mt-2 text-sm text-gray-600">Compare features and select the plan that fits your laundry shop needs.</p>
                <p class="mt-1 text-xs text-gray-500">Contact your administrator to upgrade or change your plan.</p>
            </div>
        </div>

        {{-- Plan Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-{{ count($plans) }} gap-6">
            @php
                // Sort plans: Starter/Free plans first, then Premium plans
                $sortedPlans = $plans->sortBy(function($plan) {
                    return $plan->isFree() ? 0 : 1;
                });
            @endphp
            
            @foreach($sortedPlans as $plan)
                @php
                    $isPremium = !$plan->isFree();
                    $isCurrent = $currentPlan && $currentPlan->id === $plan->id;
                    $planFeatures = $plan->features ?? [];
                @endphp

                <div class="relative bg-white rounded-xl shadow-sm border-1 transition-all {{ $isCurrent ? 'border-green-500 ring-2 ring-green-500' : ($isPremium ? 'border-indigo-500' : 'border-gray-200') }}">
                    
                    {{-- Current Plan Badge --}}
                    @if($isCurrent)
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-green-600 text-white shadow-md">
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                Current Plan
                            </span>
                        </div>
                    @elseif($isPremium)
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-indigo-600 text-white shadow-md">
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd" /></svg>
                                Recommended
                            </span>
                        </div>
                    @endif

                    <div class="p-6 {{ $isCurrent || $isPremium ? 'pt-8' : '' }}">
                        {{-- Plan Name & Price --}}
                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900">{{ $plan->name }}</h3>
                            @if($plan->description)
                                <p class="mt-1 text-sm text-gray-500">{{ $plan->description }}</p>
                            @endif
                            <div class="mt-4 mb-6">
                                @if($plan->isFree())
                                    <span class="text-4xl font-extrabold text-gray-900">Free</span>
                                @else
                                    <span class="text-4xl font-extrabold text-gray-900">₱{{ number_format((float) $plan->price, 0) }}</span>
                                    <span class="text-base font-medium text-gray-500">/{{ $plan->billing_cycle }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- CTA Button --}}
                        @if($isCurrent)
                            <button disabled class="w-full px-4 py-3 rounded-lg text-sm font-bold bg-gray-100 text-gray-400 cursor-not-allowed">
                                Current Plan
                            </button>
                        @else
                            @if($plan->isFree())
                                <a href="mailto:support@laundrytrack.com?subject=Downgrade Request&body=I would like to downgrade to the {{ $plan->name }} plan." 
                                   class="block w-full px-4 py-3 rounded-lg text-sm font-bold text-center bg-gray-900 text-white hover:bg-gray-800 transition">
                                    Contact Admin to Downgrade
                                </a>
                            @else
                                <a href="{{ route('tenant.subscription.upgrade', ['plan' => $plan->id]) }}" 
                                   class="block w-full px-4 py-3 rounded-lg text-sm font-bold text-center bg-indigo-600 text-white hover:bg-indigo-700 transition">
                                    Upgrade Now
                                </a>
                            @endif
                        @endif

                        {{-- Limits --}}
                        <div class="mt-6 pt-6 border-t border-gray-100">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Plan Limits</p>
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">Staff accounts</span>
                                    <span class="font-semibold text-gray-900">{{ $plan->staff_limit_display }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">Customers</span>
                                    <span class="font-semibold text-gray-900">{{ $plan->customer_limit_display }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">Orders / month</span>
                                    <span class="font-semibold text-gray-900">{{ $plan->order_limit_display }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Features --}}
                        <div class="mt-6 pt-6 border-t border-gray-100">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Features</p>
                            <ul class="space-y-2.5">
                                @foreach($allFeatures as $featureKey => $featureConfig)
                                    <li class="flex items-start gap-2.5 text-sm {{ in_array($featureKey, $planFeatures) ? 'text-gray-700' : 'text-gray-300' }}">
                                        @if(in_array($featureKey, $planFeatures))
                                            <svg class="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        @else
                                            <svg class="h-5 w-5 text-gray-300 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                        @endif
                                        <span>{{ $featureConfig['label'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Contact Info --}}
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg shadow-sm p-6 text-white">
            <div class="text-center">
                <h3 class="text-lg font-bold mb-2">Need Help Choosing?</h3>
                <p class="text-indigo-100 text-sm mb-4">Contact your administrator to discuss which plan is right for your business or to upgrade your current subscription.</p>
                <p class="text-xs text-indigo-200">All plans include a 30-day free trial. Cancel anytime during the trial at no cost.</p>
            </div>
        </div>
    </div>
</x-tenant-layout>
