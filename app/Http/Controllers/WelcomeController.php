<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\View\View;

class WelcomeController extends Controller
{
    /**
     * Display the central app landing page.
     */
    public function __invoke(): View
    {
        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $stats = [
            'shops' => Tenant::count(),
        ];

        return view('welcome', compact('plans', 'stats'));
    }
}
