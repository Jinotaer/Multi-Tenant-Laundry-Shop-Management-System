<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\AnalyticsController;
use App\Http\Controllers\Tenant\Auth\ForgotPasswordController;
use App\Http\Controllers\Tenant\Auth\LoginController;
use App\Http\Controllers\Tenant\Auth\RegisterController;
use App\Http\Controllers\Tenant\Auth\ResetPasswordController;
use App\Http\Controllers\Tenant\BillingController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\CustomerPortalController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\ExpenseController;
use App\Http\Controllers\Tenant\InventoryController;
use App\Http\Controllers\Tenant\NotificationController;
use App\Http\Controllers\Tenant\OrderController;
use App\Http\Controllers\Tenant\OrderPaymentController;
use App\Http\Controllers\Tenant\PaymentController;
use App\Http\Controllers\Tenant\ProfileController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\RoleController;
use App\Http\Controllers\Tenant\ServiceController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\StaffController;
use App\Http\Controllers\Tenant\SubscriptionController;
use App\Http\Controllers\Tenant\SupportTicketController;
use App\Http\Controllers\Tenant\UpdateController;
use App\Http\Middleware\CheckTenantEnabled;
use App\Http\Middleware\CheckTrialStatus;
use App\Http\Middleware\TrackTenantUsage;
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
    TrackTenantUsage::class,
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
        Route::get('/forgot-password', [
            ForgotPasswordController::class,
            'create',
        ])->name('tenant.password.request');
        Route::post('/forgot-password', [
            ForgotPasswordController::class,
            'store',
        ])->name('tenant.password.email');
        Route::get('/reset-password/{token}', [
            ResetPasswordController::class,
            'create',
        ])->name('tenant.password.reset');
        Route::post('/reset-password', [
            ResetPasswordController::class,
            'store',
        ])->name('tenant.password.update');
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

            Route::post('/orders/{order}/payment/checkout', [
                OrderPaymentController::class,
                'checkout',
            ])->name('tenant.order-payments.checkout');
            Route::get('/orders/{order}/payment/success', [
                OrderPaymentController::class,
                'success',
            ])->name('tenant.order-payments.success');

            // === Owner & Staff routes (day-to-day operations) ===
            Route::middleware('role:owner,staff')->group(function () {
                // Customers
                Route::get('/customers', [CustomerController::class, 'index'])
                    ->middleware('permission:customers.view')
                    ->name('tenant.customers.index');
                Route::get('/customers/create', [CustomerController::class, 'create'])
                    ->middleware('permission:customers.create')
                    ->name('tenant.customers.create');
                Route::post('/customers', [CustomerController::class, 'store'])
                    ->middleware('permission:customers.create')
                    ->name('tenant.customers.store');
                Route::get('/customers/{customer}', [CustomerController::class, 'show'])
                    ->middleware('permission:customers.view')
                    ->name('tenant.customers.show');
                Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])
                    ->middleware('permission:customers.update')
                    ->name('tenant.customers.edit');
                Route::put('/customers/{customer}', [CustomerController::class, 'update'])
                    ->middleware('permission:customers.update')
                    ->name('tenant.customers.update');
                Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])
                    ->middleware('permission:customers.delete')
                    ->name('tenant.customers.destroy');

                // Orders
                Route::get('/orders', [OrderController::class, 'index'])
                    ->name('tenant.orders.index');
                Route::get('/orders/create', [OrderController::class, 'create'])
                    ->name('tenant.orders.create');
                Route::post('/orders', [OrderController::class, 'store'])
                    ->name('tenant.orders.store');
                Route::get('/orders/{order}', [OrderController::class, 'show'])
                    ->name('tenant.orders.show');
                Route::get('/orders/{order}/edit', [OrderController::class, 'edit'])
                    ->name('tenant.orders.edit');
                Route::put('/orders/{order}', [OrderController::class, 'update'])
                    ->name('tenant.orders.update');
                Route::delete('/orders/{order}', [OrderController::class, 'destroy'])
                    ->name('tenant.orders.destroy');

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
                Route::patch('/orders/{order}/redeem-loyalty', [
                    OrderController::class,
                    'redeemLoyalty',
                ])->name('tenant.orders.redeem-loyalty');

                // Staff Management (RBAC)
                Route::get('/staff', [StaffController::class, 'index'])
                    ->middleware('permission:staff.view,staff.create,staff.update,staff.delete,staff.assign_roles')
                    ->name('tenant.staff.index');
                Route::get('/staff/create', [StaffController::class, 'create'])
                    ->middleware('permission:staff.create')
                    ->name('tenant.staff.create');
                Route::post('/staff', [StaffController::class, 'store'])
                    ->middleware('permission:staff.create')
                    ->name('tenant.staff.store');
                Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])
                    ->middleware('permission:staff.update')
                    ->name('tenant.staff.edit');
                Route::put('/staff/{staff}', [StaffController::class, 'update'])
                    ->middleware('permission:staff.update')
                    ->name('tenant.staff.update');
                Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])
                    ->middleware('permission:staff.delete')
                    ->name('tenant.staff.destroy');
                Route::get('/roles', [RoleController::class, 'index'])
                    ->middleware('permission:roles.view,roles.create,roles.update,roles.delete')
                    ->name('tenant.roles.index');
                Route::post('/roles', [RoleController::class, 'store'])
                    ->middleware('permission:roles.create')
                    ->name('tenant.roles.store');
                Route::patch('/roles/{role}', [RoleController::class, 'update'])
                    ->middleware('permission:roles.update')
                    ->name('tenant.roles.update');
                Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
                    ->middleware('permission:roles.delete')
                    ->name('tenant.roles.destroy');

            });

            // === Owner & staff permission-gated management routes ===
            Route::middleware('role:owner,staff')->group(function () {
                // Services & Pricing
                Route::get('/services', [ServiceController::class, 'index'])
                    ->middleware('permission:services.view,services.create,services.update,services.delete')
                    ->name('tenant.services.index');
                Route::get('/services/create', [ServiceController::class, 'create'])
                    ->middleware('permission:services.create')
                    ->name('tenant.services.create');
                Route::post('/services', [ServiceController::class, 'store'])
                    ->middleware('permission:services.create')
                    ->name('tenant.services.store');
                Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])
                    ->middleware('permission:services.update')
                    ->name('tenant.services.edit');
                Route::put('/services/{service}', [ServiceController::class, 'update'])
                    ->middleware('permission:services.update')
                    ->name('tenant.services.update');
                Route::delete('/services/{service}', [ServiceController::class, 'destroy'])
                    ->middleware('permission:services.delete')
                    ->name('tenant.services.destroy');

                // Expense Tracking (Premium only)
                Route::middleware('feature:expense_tracking')->group(function () {
                    Route::get('/expenses', [ExpenseController::class, 'index'])
                        ->middleware('permission:expenses.view,expenses.create,expenses.update,expenses.delete')
                        ->name('tenant.expenses.index');
                    Route::get('/expenses/create', [ExpenseController::class, 'create'])
                        ->middleware('permission:expenses.create')
                        ->name('tenant.expenses.create');
                    Route::post('/expenses', [ExpenseController::class, 'store'])
                        ->middleware('permission:expenses.create')
                        ->name('tenant.expenses.store');
                    Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])
                        ->middleware('permission:expenses.update')
                        ->name('tenant.expenses.edit');
                    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])
                        ->middleware('permission:expenses.update')
                        ->name('tenant.expenses.update');
                    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])
                        ->middleware('permission:expenses.delete')
                        ->name('tenant.expenses.destroy');
                });

                // Reports (Premium only)
                Route::middleware('feature:reports')
                    ->get('/reports', [ReportController::class, 'index'])
                    ->middleware('permission:reports.view,reports.export')
                    ->name('tenant.reports.index');
                Route::middleware('feature:reports')
                    ->get('/reports/export/excel', [ReportController::class, 'exportExcel'])
                    ->middleware('permission:reports.export')
                    ->name('tenant.reports.export-excel');
                Route::middleware('feature:reports')
                    ->get('/reports/export/pdf', [ReportController::class, 'exportPdf'])
                    ->middleware('permission:reports.export')
                    ->name('tenant.reports.export-pdf');
                Route::middleware('feature:analytics_dashboard')
                    ->get('/analytics', [AnalyticsController::class, 'index'])
                    ->middleware('permission:analytics.view')
                    ->name('tenant.analytics.index');
                Route::middleware('feature:inventory_management')->group(function () {
                    Route::get('/inventory', [InventoryController::class, 'index'])->middleware('permission:inventory.view,inventory.create,inventory.update,inventory.delete,inventory.adjust')->name('tenant.inventory.index');
                    Route::get('/inventory/create', [InventoryController::class, 'create'])->middleware('permission:inventory.create')->name('tenant.inventory.create');
                    Route::post('/inventory', [InventoryController::class, 'store'])->middleware('permission:inventory.create')->name('tenant.inventory.store');
                    Route::get('/inventory/{inventory}/edit', [InventoryController::class, 'edit'])->middleware('permission:inventory.update')->name('tenant.inventory.edit');
                    Route::put('/inventory/{inventory}', [InventoryController::class, 'update'])->middleware('permission:inventory.update')->name('tenant.inventory.update');
                    Route::delete('/inventory/{inventory}', [InventoryController::class, 'destroy'])->middleware('permission:inventory.delete')->name('tenant.inventory.destroy');
                    Route::post('/inventory/{inventory}/adjust', [InventoryController::class, 'adjust'])->middleware('permission:inventory.adjust')->name('tenant.inventory.adjust');
                });

                // Subscription
                Route::get('/subscription', [
                    SubscriptionController::class,
                    'index',
                ])->middleware('permission:subscription.manage')
                    ->name('tenant.subscription');
                Route::get('/subscription/plans', [
                    SubscriptionController::class,
                    'plans',
                ])->middleware('permission:subscription.manage')
                    ->name('tenant.subscription.plans');
                Route::get('/subscription/renew', [
                    \App\Http\Controllers\Tenant\SubscriptionRenewalController::class,
                    'show',
                ])->middleware('permission:subscription.manage')
                    ->name('tenant.subscription.renew');
                Route::post('/subscription/renew/checkout', [
                    \App\Http\Controllers\Tenant\SubscriptionRenewalController::class,
                    'checkout',
                ])->middleware('permission:subscription.manage')
                    ->name('tenant.subscription.renew.checkout');
                Route::get('/subscription/renew/success', [
                    \App\Http\Controllers\Tenant\SubscriptionRenewalController::class,
                    'success',
                ])->middleware('permission:subscription.manage')
                    ->name('tenant.subscription.renew.success');
                Route::get('/subscription/upgrade', [
                    \App\Http\Controllers\Tenant\SubscriptionUpgradeController::class,
                    'show',
                ])->middleware('permission:subscription.manage')
                    ->name('tenant.subscription.upgrade');
                Route::post('/subscription/upgrade/checkout', [
                    \App\Http\Controllers\Tenant\SubscriptionUpgradeController::class,
                    'checkout',
                ])->middleware('permission:subscription.manage')
                    ->name('tenant.subscription.upgrade.checkout');
                Route::get('/subscription/upgrade/success', [
                    \App\Http\Controllers\Tenant\SubscriptionUpgradeController::class,
                    'success',
                ])->middleware('permission:subscription.manage')
                    ->name('tenant.subscription.upgrade.success');

                // Billing & Invoices
                Route::get('/billing', [BillingController::class, 'index'])->middleware('permission:billing.view')->name('tenant.billing.index');
                Route::get('/billing/{invoice}', [BillingController::class, 'show'])->middleware('permission:billing.view')->name('tenant.billing.show');
                Route::get('/billing/{invoice}/download', [BillingController::class, 'download'])->middleware('permission:billing.download')->name('tenant.billing.download');

                // Owner-only premium support
                Route::middleware(['role:owner', 'feature:priority_support'])->group(function () {
                    Route::get('/support', [SupportTicketController::class, 'index'])->name('tenant.support.index');
                    Route::post('/support', [SupportTicketController::class, 'store'])->name('tenant.support.store');
                    Route::get('/support/{ticket}', [SupportTicketController::class, 'show'])->name('tenant.support.show');
                    Route::post('/support/{ticket}/message', [SupportTicketController::class, 'sendMessage'])->name('tenant.support.message');
                });
            });

            // === Owner-only routes (management) ===
            Route::middleware('role:owner')->group(function () {

                // Settings
                Route::get('/settings', function () {
                    return redirect()->route('tenant.settings.profile');
                })->name('tenant.settings.index');

                // Layout defaults & logo
                Route::patch('/settings/theme', [
                    SettingsController::class,
                    'updateTheme',
                ])->name('tenant.settings.theme.update');
                Route::middleware('feature:custom_branding')->group(function () {
                    Route::post('/settings/logo', [
                        SettingsController::class,
                        'updateLogo',
                    ])->name('tenant.settings.logo');
                    Route::delete('/settings/logo', [
                        SettingsController::class,
                        'removeLogo',
                    ])->name('tenant.settings.logo.remove');
                });

                Route::get('/updates', [UpdateController::class, 'index'])->name('tenant.updates.index');
                Route::post('/updates/{release}/apply', [UpdateController::class, 'update'])->name('tenant.updates.apply');
                Route::post('/updates/{release}/rollback', [UpdateController::class, 'rollback'])->name('tenant.updates.rollback');
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
