<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TenantApproved;
use App\Mail\TenantRejected;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantRegistration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function index(): View
    {
        $registrations = TenantRegistration::latest()->paginate(15);

        return view('admin.registrations.index', compact('registrations'));
    }

    public function approve(TenantRegistration $registration): RedirectResponse
    {
        if (! $registration->isPending()) {
            return back()->with('error', 'Only pending registrations can be approved.');
        }

        $domain = $registration->subdomain . '.localhost';

        // Clean up any orphaned tenant/database from a previous failed attempt.
        $existingTenant = Tenant::find($registration->subdomain);
        if ($existingTenant) {
            $existingTenant->delete();
        } else {
            $dbName = config('tenancy.database.prefix').$registration->subdomain.config('tenancy.database.suffix');
            $exists = DB::select('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$dbName]);
            if ($exists) {
                DB::statement("DROP DATABASE `{$dbName}`");
            }
        }

        try {
            $selectedPlan = $registration->subscription_plan_id
                ? SubscriptionPlan::find($registration->subscription_plan_id)
                : SubscriptionPlan::getDefault();

            $isPremium = $selectedPlan && ! $selectedPlan->isFree();

            $tenant = Tenant::create([
                'id' => $registration->subdomain,
                'subscription_plan_id' => $selectedPlan?->id,
                'features' => $selectedPlan?->features ?? [],
                'trial_ends_at' => $isPremium ? null : now()->addDays(30),
                'is_paid' => false,
                'data' => [
                    'shop_name' => $registration->shop_name,
                ],
            ]);

            $tenant->domains()->create(['domain' => $domain]);

            $tenant->run(function () use ($registration) {
                User::create([
                    'name' => $registration->owner_name,
                    'email' => $registration->owner_email,
                    'password' => $registration->owner_password,
                    'role' => 'owner',
                ]);
            });
        } catch (\Exception $e) {
            if (isset($tenant)) {
                $tenant->delete();
            }

            return back()->withErrors(['shop_name' => "Failed to create tenant: {$e->getMessage()}"]);
        }

        $registration->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        Mail::to($registration->owner_email)->send(new TenantApproved($registration, $domain));

        return back()->with('success', "'{$registration->shop_name}' has been approved and tenant created.");
    }

    public function reject(Request $request, TenantRegistration $registration): RedirectResponse
    {
        if (! $registration->isPending()) {
            return back()->with('error', 'Only pending registrations can be rejected.');
        }

        $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $registration->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('rejection_reason'),
            'rejected_at' => now(),
        ]);

        Mail::to($registration->owner_email)->send(new TenantRejected($registration));

        return back()->with('success', "'{$registration->shop_name}' has been rejected.");
    }
}
