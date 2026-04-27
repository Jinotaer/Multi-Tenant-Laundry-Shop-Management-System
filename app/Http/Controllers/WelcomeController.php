<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;
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

        $admin = Admin::first();
        $platformLogoUrl = ($admin && $admin->logo_path && Storage::disk('public')->exists($admin->logo_path))
            ? asset('storage/' . $admin->logo_path)
            : null;

        return view('welcome', compact('plans', 'stats', 'platformLogoUrl'));
    }
}
