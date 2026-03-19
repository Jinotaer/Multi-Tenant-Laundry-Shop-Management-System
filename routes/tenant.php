<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\Auth\LoginController;
use App\Http\Controllers\Tenant\Auth\RegisterController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\CustomerPortalController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\ExpenseController;
use App\Http\Controllers\Tenant\NotificationController;
use App\Http\Controllers\Tenant\OrderController;
use App\Http\Controllers\Tenant\PaymentController;
use App\Http\Controllers\Tenant\ProfileController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\ServiceController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\StaffController;
use App\Http\Controllers\Tenant\SubscriptionController;
use App\Http\Middleware\CheckTenantEnabled;
use App\Http\Middleware\CheckTrialStatus;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenancyServiceProvider.
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    CheckTenantEnabled::class,
])->group(function () {
    // Guest routes
    Route::middleware('guest:web,customer')->group(function () {
        Route::get('/login', [LoginController::class, 'create'])->name(
            'tenant.login',
        );
        Route::post('/login', [LoginController::class, 'store']);
        Route::get('/register', [RegisterController::class, 'create'])->name(
            'tenant.register',
        );
        Route::post('/register', [RegisterController::class, 'store']);
    });

    // Authenticated routes
    Route::middleware('tenant.auth')->group(function () {
        Route::post('/logout', [LoginController::class, 'destroy'])->name(
            'tenant.logout',
        );

        // Trial expired page — accessible even when trial is expired
        Route::get('/trial-expired', function () {
            $tenant = tenant();

            // If tenant has active subscription, redirect to dashboard
            if ($tenant && $tenant->hasActiveSubscription()) {
                return redirect()->route('tenant.dashboard');
            }

            return view('tenant.trial-expired');
        })->name('tenant.trial-expired');

        // Payment routes — accessible before paying (outside CheckTrialStatus)
        Route::get('/payment', [PaymentController::class, 'show'])->name(
            'tenant.payment.show',
        );
        Route::post('/payment/checkout', [
            PaymentController::class,
            'checkout',
        ])->name('tenant.payment.checkout');
        Route::get('/payment/success', [
            PaymentController::class,
            'success',
        ])->name('tenant.payment.success');

        // All routes below require active trial or paid subscription
        Route::middleware(CheckTrialStatus::class)->group(function () {
            Route::get('/dashboard', [
                DashboardController::class,
                'index',
            ])->name('tenant.dashboard');
            Route::get('/settings/theme', [
                SettingsController::class,
                'index',
            ])->name('tenant.settings.theme');
            Route::patch('/settings/theme/preferences', [
                SettingsController::class,
                'updatePreferences',
            ])->name('tenant.settings.theme.preferences.update');
            Route::delete('/settings/theme/preferences', [
                SettingsController::class,
                'resetPreferences',
            ])->name('tenant.settings.theme.preferences.reset');

            // === Owner & Staff routes (day-to-day operations) ===
            Route::middleware('role:owner,staff')->group(function () {
                // Customers
                Route::resource('customers', CustomerController::class)->names([
                    'index' => 'tenant.customers.index',
                    'create' => 'tenant.customers.create',
                    'store' => 'tenant.customers.store',
                    'show' => 'tenant.customers.show',
                    'edit' => 'tenant.customers.edit',
                    'update' => 'tenant.customers.update',
                    'destroy' => 'tenant.customers.destroy',
                ]);

                // Orders
                Route::resource('orders', OrderController::class)->names([
                    'index' => 'tenant.orders.index',
                    'create' => 'tenant.orders.create',
                    'store' => 'tenant.orders.store',
                    'show' => 'tenant.orders.show',
                    'edit' => 'tenant.orders.edit',
                    'update' => 'tenant.orders.update',
                    'destroy' => 'tenant.orders.destroy',
                ]);

                // Order quick actions
                Route::patch('/orders/{order}/status', [
                    OrderController::class,
                    'updateStatus',
                ])->name('tenant.orders.update-status');
                Route::patch('/orders/{order}/mark-paid', [
                    OrderController::class,
                    'markPaid',
                ])->name('tenant.orders.mark-paid');
                Route::get('/orders/{order}/receipt', [
                    OrderController::class,
                    'receipt',
                ])->name('tenant.orders.receipt');

            });

            // Workspace logo
            Route::post('/settings/logo', [
                SettingsController::class,
                'updateLogo',
            ])->name('tenant.settings.logo');
            Route::delete('/settings/logo', [
                SettingsController::class,
                'removeLogo',
            ])->name('tenant.settings.logo.remove');

            // === Owner-only routes (management) ===
            Route::middleware('role:owner')->group(function () {
                // Services & Pricing
                Route::resource('services', ServiceController::class)
                    ->except(['show'])
                    ->names([
                        'index' => 'tenant.services.index',
                        'create' => 'tenant.services.create',
                        'store' => 'tenant.services.store',
                        'edit' => 'tenant.services.edit',
                        'update' => 'tenant.services.update',
                        'destroy' => 'tenant.services.destroy',
                    ]);

                // Staff Management
                Route::resource('staff', StaffController::class)
                    ->except(['show'])
                    ->names([
                        'index' => 'tenant.staff.index',
                        'create' => 'tenant.staff.create',
                        'store' => 'tenant.staff.store',
                        'edit' => 'tenant.staff.edit',
                        'update' => 'tenant.staff.update',
                        'destroy' => 'tenant.staff.destroy',
                    ]);

                // Expense Tracking (Premium only)
                Route::middleware('feature:expense_tracking')
                    ->resource('expenses', ExpenseController::class)
                    ->except(['show'])
                    ->names([
                        'index' => 'tenant.expenses.index',
                        'create' => 'tenant.expenses.create',
                        'store' => 'tenant.expenses.store',
                        'edit' => 'tenant.expenses.edit',
                        'update' => 'tenant.expenses.update',
                        'destroy' => 'tenant.expenses.destroy',
                    ]);

                // Reports (Premium only)
                Route::middleware('feature:reports')
                    ->get('/reports', [ReportController::class, 'index'])
                    ->name('tenant.reports.index');

                // Subscription
                Route::get('/subscription', [
                    SubscriptionController::class,
                    'index',
                ])->name('tenant.subscription');

                // Settings
                Route::get('/settings', function () {
                    return redirect()->route('tenant.settings.profile');
                })->name('tenant.settings.index');

                // Layout defaults & logo
                Route::patch('/settings/theme', [
                    SettingsController::class,
                    'updateTheme',
                ])->name('tenant.settings.theme.update');
            });

            // === Customer portal routes (Premium only) ===
            Route::middleware([
                'role:customer',
                'feature:customer_portal',
            ])->group(function () {
                Route::get('/portal', [
                    CustomerPortalController::class,
                    'index',
                ])->name('tenant.portal.index');
                Route::get('/portal/{order}', [
                    CustomerPortalController::class,
                    'show',
                ])->name('tenant.portal.show');
            });

            // Notifications (feature gated for all authenticated users)
            Route::middleware('feature:notifications')->group(function () {
                Route::get('/notifications', [
                    NotificationController::class,
                    'index',
                ])->name('tenant.notifications.index');
                Route::get('/notifications/feed', [
                    NotificationController::class,
                    'feed',
                ])->name('tenant.notifications.feed');
                Route::patch('/notifications/read-all', [
                    NotificationController::class,
                    'markAllRead',
                ])->name('tenant.notifications.mark-all-read');
            });

            // Layout Preferences — staff/customer personal overrides
            Route::patch('/settings/layout', [
                SettingsController::class,
                'updatePreferences',
            ])->name('tenant.settings.layout.update');
            Route::delete('/settings/layout/reset', [
                SettingsController::class,
                'resetFromCustomizer',
            ])->name('tenant.settings.layout.reset');

            // Live customizer — AJAX endpoint (all roles)
            Route::post('/settings/layout/save', [
                SettingsController::class,
                'saveFromCustomizer',
            ])->name('tenant.settings.layout.save');

            // Profile (all authenticated roles)
            Route::get('/settings/profile', [
                ProfileController::class,
                'edit',
            ])->name('tenant.settings.profile');
            Route::patch('/settings/profile', [
                ProfileController::class,
                'update',
            ])->name('tenant.settings.profile.update');
            Route::put('/settings/password', [
                ProfileController::class,
                'updatePassword',
            ])->name('tenant.settings.password');
            Route::delete('/settings/profile', [
                ProfileController::class,
                'destroy',
            ])->name('tenant.settings.profile.destroy');
        });
    });

    // Redirect root to dashboard or login
    Route::get('/', function () {
        if (
            auth()->guard('web')->check() ||
            auth()->guard('customer')->check()
        ) {
            return redirect()->route('tenant.dashboard');
        }

        return redirect()->route('tenant.login');
    })->name('tenant.home');
});


use App\Http\Controllers\Tenant\UpdateController;
// To be placed in the appropriate middleware group where settings are
Route::middleware([
    'web',
    \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
    \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
    'auth:web',
])->group(function () {
    Route::get('updates', [UpdateController::class, 'index'])->name('tenant.updates.index');
    Route::post('updates/{release}/apply', [UpdateController::class, 'update'])->name('tenant.updates.apply');
    Route::post('updates/{release}/rollback', [UpdateController::class, 'rollback'])->name('tenant.updates.rollback');
});

