<?php

namespace App\Http\Controllers;

use App\Mail\TenantApproved;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantRegistration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class TenantRegistrationController extends Controller
{
    /**
     * Display the plan selection page.
     */
    public function pricing(): View
    {
        $plans = SubscriptionPlan::active()->orderBy('sort_order')->get();

        return view('tenant.pricing', compact('plans'));
    }

    /**
     * Display the shop registration form.
     */
    public function create(Request $request): View
    {
        $selectedPlan = null;

        if ($request->has('plan')) {
            $selectedPlan = SubscriptionPlan::where('is_active', true)->find($request->plan);
        }

        // If no plan selected via query, fall back to default
        if (! $selectedPlan) {
            $selectedPlan = SubscriptionPlan::getDefault();
        }

        return view('tenant.register-shop', compact('selectedPlan'));
    }

    /**
     * Handle the shop registration — creates the tenant immediately (no admin approval required).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'shop_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:tenant_registrations,owner_email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'subscription_plan_id' => ['nullable', 'exists:subscription_plans,id'],
        ]);

        $subdomain = Str::slug($request->shop_name);
        $original = $subdomain;
        $counter = 1;

        while (
            TenantRegistration::where('subdomain', $subdomain)->exists()
            || \Stancl\Tenancy\Database\Models\Domain::where('domain', $subdomain.'.localhost')->exists()
        ) {
            $subdomain = $original.'-'.$counter;
            $counter++;
        }

        $planId = $request->subscription_plan_id
            ?? SubscriptionPlan::getDefault()?->id;

        $registration = TenantRegistration::create([
            'shop_name' => $request->shop_name,
            'subdomain' => $subdomain,
            'owner_name' => $request->name,
            'owner_email' => $request->email,
            'owner_password' => $request->password,
            'subscription_plan_id' => $planId,
            'status' => 'pending',
        ]);

        // Automatically create the tenant (no admin approval needed)
        $domain = $subdomain.'.localhost';

        // Keep registration in a pending state — admin will approve or reject.
        // (Approval used to auto-create tenant; that action now happens from the admin panel.)

        return redirect()->route('shop.pending');
    }

    /**
     * Show the registration success page.
     */
    public function pending(): View
    {
        return view('tenant.pending');
    }
}
