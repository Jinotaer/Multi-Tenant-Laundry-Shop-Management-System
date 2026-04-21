<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Available Plans - {{ tenant()->data['shop_name'] ?? tenant()->id }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif; background-color: #f3f4f6; min-height: 100vh; padding: 2rem 1rem; }
            .container { max-width: 80rem; margin: 0 auto; }
            .header { text-align: center; margin-bottom: 3rem; }
            .header h1 { font-size: 2rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem; }
            .header p { color: #6b7280; font-size: 1rem; }
            .plans-grid { display: grid; grid-template-columns: 1fr; gap: 2rem; margin-bottom: 2rem; }
            @media (min-width: 768px) { .plans-grid { grid-template-columns: repeat(2, 1fr); } }
            .plan-card { background: #fff; border-radius: 1rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,.1); padding: 2rem; position: relative; border: 2px solid #e5e7eb; }
            .plan-card.current { border-color: #10b981; }
            .plan-card.premium { border-color: #6366f1; }
            .badge { position: absolute; top: -0.75rem; left: 50%; transform: translateX(-50%); padding: 0.375rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; color: #fff; display: inline-flex; align-items: center; gap: 0.25rem; }
            .badge.current { background: #10b981; }
            .badge.premium { background: #6366f1; }
            .badge svg { width: 0.875rem; height: 0.875rem; }
            .plan-header { text-align: center; margin-bottom: 1.5rem; padding-top: 0.5rem; }
            .plan-name { font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem; }
            .plan-desc { font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem; }
            .plan-price { font-size: 2.5rem; font-weight: 800; color: #111827; }
            .plan-price-cycle { font-size: 1rem; font-weight: 500; color: #6b7280; }
            .btn { display: block; width: 100%; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; text-align: center; text-decoration: none; transition: all 0.15s; border: none; cursor: pointer; margin-bottom: 1.5rem; }
            .btn-primary { background: linear-gradient(135deg, #6366f1, #7c3aed); color: #fff; }
            .btn-primary:hover { opacity: 0.9; }
            .btn-secondary { background: #111827; color: #fff; }
            .btn-secondary:hover { background: #1f2937; }
            .btn-disabled { background: #f3f4f6; color: #9ca3af; cursor: not-allowed; }
            .section { border-top: 1px solid #e5e7eb; padding-top: 1.5rem; margin-top: 1.5rem; }
            .section-title { font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem; }
            .limits { display: flex; flex-direction: column; gap: 0.625rem; }
            .limit-item { display: flex; justify-content: space-between; font-size: 0.875rem; }
            .limit-label { color: #6b7280; }
            .limit-value { font-weight: 600; color: #111827; }
            .features { list-style: none; padding: 0; }
            .feature-item { display: flex; align-items: flex-start; gap: 0.625rem; padding: 0.5rem 0; font-size: 0.875rem; }
            .feature-item.enabled { color: #374151; }
            .feature-item.disabled { color: #d1d5db; }
            .feature-item svg { width: 1.25rem; height: 1.25rem; flex-shrink: 0; margin-top: 0.125rem; }
            .feature-item.enabled svg { color: #10b981; }
            .feature-item.disabled svg { color: #d1d5db; }
            .footer { background: linear-gradient(135deg, #6366f1, #7c3aed); border-radius: 0.75rem; padding: 2rem; text-align: center; color: #fff; }
            .footer h3 { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; }
            .footer p { color: rgba(255,255,255,0.9); font-size: 0.875rem; margin-bottom: 0.5rem; }
            .footer .small { font-size: 0.75rem; color: rgba(255,255,255,0.7); }
            .logout-link { display: inline-block; margin-top: 1.5rem; padding: 0.5rem 1rem; color: #6b7280; font-size: 0.875rem; text-decoration: none; }
            .logout-link:hover { color: #374151; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Choose the Right Plan for Your Business</h1>
                <p>Compare features and select the plan that fits your laundry shop needs.</p>
            </div>

            <div class="plans-grid">
                @php
                    $sortedPlans = $plans->sortBy(function($plan) { return $plan->isFree() ? 0 : 1; });
                @endphp
                
                @foreach($sortedPlans as $plan)
                    @php
                        $isPremium = !$plan->isFree();
                        $isCurrent = $currentPlan && $currentPlan->id === $plan->id;
                        $planFeatures = $plan->features ?? [];
                    @endphp

                    <div class="plan-card {{ $isCurrent ? 'current' : ($isPremium ? 'premium' : '') }}">
                        @if($isCurrent)
                            <span class="badge current">
                                <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                Current Plan
                            </span>
                        @elseif($isPremium)
                            <span class="badge premium">
                                <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd" /></svg>
                                Recommended
                            </span>
                        @endif

                        <div class="plan-header">
                            <h2 class="plan-name">{{ $plan->name }}</h2>
                            @if($plan->description)
                                <p class="plan-desc">{{ $plan->description }}</p>
                            @endif
                            <div>
                                @if($plan->isFree())
                                    <span class="plan-price">Free</span>
                                @else
                                    <span class="plan-price">₱{{ number_format((float) $plan->price, 0) }}</span>
                                    <span class="plan-price-cycle">/{{ $plan->billing_cycle }}</span>
                                @endif
                            </div>
                        </div>

                        @if($isCurrent)
                            <button disabled class="btn btn-disabled">Current Plan</button>
                        @else
                            <a href="{{ route('tenant.subscription.change-plan.confirm', ['plan' => $plan->id]) }}" class="btn {{ $plan->isFree() ? 'btn-secondary' : 'btn-primary' }}">
                                {{ $plan->isFree() ? 'Downgrade to Free' : 'Upgrade Now' }}
                            </a>
                        @endif

                        <div class="section">
                            <p class="section-title">Plan Limits</p>
                            <div class="limits">
                                <div class="limit-item">
                                    <span class="limit-label">Staff accounts</span>
                                    <span class="limit-value">{{ $plan->staff_limit_display }}</span>
                                </div>
                                <div class="limit-item">
                                    <span class="limit-label">Customers</span>
                                    <span class="limit-value">{{ $plan->customer_limit_display }}</span>
                                </div>
                                <div class="limit-item">
                                    <span class="limit-label">Orders / month</span>
                                    <span class="limit-value">{{ $plan->order_limit_display }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="section">
                            <p class="section-title">Features</p>
                            <ul class="features">
                                @foreach($allFeatures as $featureKey => $featureConfig)
                                    <li class="feature-item {{ in_array($featureKey, $planFeatures) ? 'enabled' : 'disabled' }}">
                                        @if(in_array($featureKey, $planFeatures))
                                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        @else
                                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                        @endif
                                        <span>{{ $featureConfig['label'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="footer">
                <h3>Need Help Choosing?</h3>
                <p>Contact your administrator to discuss which plan is right for your business or to upgrade your current subscription.</p>
                <p class="small">All plans include a 30-day free trial. Cancel anytime during the trial at no cost.</p>
            </div>
        </div>
    </body>
</html>
