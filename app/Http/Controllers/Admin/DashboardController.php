<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\TenantRegistration;
use App\Services\AdminLayoutSettingsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(AdminLayoutSettingsService $adminLayoutSettingsService): View
    {
        $admin = auth('admin')->user();
        $approvedTenants = Tenant::query()
            ->approved()
            ->with(['registration', 'subscriptionPlan', 'domains'])
            ->get();

        $totalTenants = $approvedTenants->count();
        $pendingRegistrations = TenantRegistration::where('status', 'pending')->count();
        $activeWorkspaces = $approvedTenants
            ->filter(fn (Tenant $tenant): bool => $tenant->is_paid || $tenant->isOnTrial())
            ->count();
        $paidWorkspaces = $approvedTenants->where('is_paid', true)->count();
        $trialWorkspaces = $approvedTenants
            ->filter(fn (Tenant $tenant): bool => $tenant->isOnTrial())
            ->count();
        $attentionRequired = $approvedTenants
            ->filter(
                fn (Tenant $tenant): bool => $tenant->isStorageLimitExceeded()
                    || $tenant->isBandwidthLimitExceeded()
                    || $tenant->needsRenewal()
                    || $tenant->isTrialExpired()
            )
            ->count();

        $currentMonthStart = now()->startOfMonth();
        $nextMonthStart = $currentMonthStart->copy()->addMonth();
        $previousMonthStart = $currentMonthStart->copy()->subMonth();

        $currentMonthRevenue = (float) Payment::query()
            ->where('payment_type', 'subscription')
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $currentMonthStart)
            ->where('paid_at', '<', $nextMonthStart)
            ->sum('amount');

        $previousMonthRevenue = (float) Payment::query()
            ->where('payment_type', 'subscription')
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $previousMonthStart)
            ->where('paid_at', '<', $currentMonthStart)
            ->sum('amount');

        $revenueDelta = $previousMonthRevenue > 0
            ? (($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100
            : null;

        $planPalette = [
            ['hex' => '#1d4ed8', 'bar_class' => 'bg-blue-700', 'dot_class' => 'bg-blue-700', 'badge_class' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-200'],
            ['hex' => '#059669', 'bar_class' => 'bg-emerald-600', 'dot_class' => 'bg-emerald-600', 'badge_class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200'],
            ['hex' => '#7c3aed', 'bar_class' => 'bg-violet-600', 'dot_class' => 'bg-violet-600', 'badge_class' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-200'],
            ['hex' => '#0f766e', 'bar_class' => 'bg-teal-700', 'dot_class' => 'bg-teal-700', 'badge_class' => 'bg-teal-100 text-teal-700 dark:bg-teal-500/15 dark:text-teal-200'],
        ];

        $rawPlanBreakdown = $approvedTenants
            ->groupBy(fn (Tenant $tenant): string => $tenant->subscriptionPlan?->name ?? 'Unassigned')
            ->map(fn ($tenants, string $name): array => [
                'name' => $name,
                'count' => $tenants->count(),
            ])
            ->sortByDesc('count')
            ->values();

        if ($rawPlanBreakdown->count() > 4) {
            $topPlans = $rawPlanBreakdown->take(3);
            $topPlans->push([
                'name' => 'Other',
                'count' => $rawPlanBreakdown->slice(3)->sum('count'),
            ]);
            $rawPlanBreakdown = $topPlans->values();
        }

        $totalPlanCount = max(1, (int) $rawPlanBreakdown->sum('count'));
        $planBreakdown = $rawPlanBreakdown
            ->values()
            ->map(function (array $plan, int $index) use ($planPalette, $totalPlanCount): array {
                $palette = $planPalette[$index % count($planPalette)];

                return [
                    'name' => $plan['name'],
                    'count' => $plan['count'],
                    'percentage' => round(($plan['count'] / $totalPlanCount) * 100, 1),
                    'hex' => $palette['hex'],
                    'bar_class' => $palette['bar_class'],
                    'dot_class' => $palette['dot_class'],
                    'badge_class' => $palette['badge_class'],
                ];
            })
            ->values();

        $recentRegistrations = TenantRegistration::query()
            ->with('subscriptionPlan')
            ->latest()
            ->take(5)
            ->get();

        $recentRegistrationTenantIds = Tenant::query()
            ->whereIn('id', $recentRegistrations->pluck('subdomain'))
            ->pluck('id')
            ->all();

        $registrationActivity = TenantRegistration::query()
            ->latest()
            ->take(4)
            ->get()
            ->map(function (TenantRegistration $registration): array {
                return [
                    'timestamp' => $registration->updated_at ?? $registration->created_at,
                    'title' => match ($registration->status) {
                        'approved' => "{$registration->shop_name} was approved",
                        'rejected' => "{$registration->shop_name} was rejected",
                        default => "{$registration->shop_name} registration received",
                    },
                    'description' => $registration->owner_email,
                    'tone' => match ($registration->status) {
                        'approved' => 'emerald',
                        'rejected' => 'red',
                        default => 'amber',
                    },
                    'icon' => match ($registration->status) {
                        'approved' => 'check',
                        'rejected' => 'x',
                        default => 'clock',
                    },
                ];
            });

        $paymentActivity = Payment::query()
            ->with(['tenant.registration', 'subscriptionPlan'])
            ->where('payment_type', 'subscription')
            ->latest('updated_at')
            ->take(4)
            ->get()
            ->map(function (Payment $payment): array {
                $shopName = $payment->tenant?->registration?->shop_name
                    ?? $payment->tenant?->id
                    ?? 'A shop';

                return [
                    'timestamp' => $payment->paid_at ?? $payment->updated_at ?? $payment->created_at,
                    'title' => match ($payment->status) {
                        'paid' => "{$shopName} payment was received",
                        'failed' => "{$shopName} payment failed",
                        default => "{$shopName} payment is pending",
                    },
                    'description' => trim(($payment->subscriptionPlan?->name ? $payment->subscriptionPlan->name.' - ' : '').'PHP '.number_format((float) $payment->amount, 2)),
                    'tone' => match ($payment->status) {
                        'paid' => 'blue',
                        'failed' => 'red',
                        default => 'slate',
                    },
                    'icon' => match ($payment->status) {
                        'paid' => 'wallet',
                        'failed' => 'warning',
                        default => 'clock',
                    },
                ];
            });

        $activityFeed = $registrationActivity
            ->concat($paymentActivity)
            ->sortByDesc('timestamp')
            ->take(5)
            ->values();

        return view('admin.dashboard', [
            'dashboardWidgets' => $adminLayoutSettingsService->dashboardWidgetsFor($admin),
            'totalTenants' => $totalTenants,
            'pendingRegistrations' => $pendingRegistrations,
            'activeWorkspaces' => $activeWorkspaces,
            'paidWorkspaces' => $paidWorkspaces,
            'trialWorkspaces' => $trialWorkspaces,
            'attentionRequired' => $attentionRequired,
            'currentMonthRevenue' => $currentMonthRevenue,
            'previousMonthRevenue' => $previousMonthRevenue,
            'revenueDelta' => $revenueDelta,
            'planBreakdown' => $planBreakdown,
            'recentRegistrations' => $recentRegistrations,
            'recentRegistrationTenantIds' => $recentRegistrationTenantIds,
            'activityFeed' => $activityFeed,
        ]);
    }
}
