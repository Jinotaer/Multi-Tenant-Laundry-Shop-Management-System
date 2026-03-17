<?php

use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\PayMongoWebhookController;
use App\Http\Controllers\TenantRegistrationController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central Domain Routes
|--------------------------------------------------------------------------
|
| These routes are only accessible on central domains (127.0.0.1, localhost).
| Tenant routes are handled in routes/tenant.php via stancl/tenancy.
|
*/

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)
        ->middleware('web')
        ->group(function () {
            // Landing Page
            Route::get('/', WelcomeController::class)->name('home');

            // PayMongo Webhook (no CSRF verification)
            Route::post('/webhooks/paymongo', [
                PayMongoWebhookController::class,
                'handle',
            ])
                ->withoutMiddleware(
                    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
                )
                ->name('webhooks.paymongo');

            // Shop Registration (public)
            Route::get('/pricing', [
                TenantRegistrationController::class,
                'pricing',
            ])->name('shop.pricing');
            Route::get('/register-shop', [
                TenantRegistrationController::class,
                'create',
            ])->name('shop.register');
            Route::post('/register-shop', [
                TenantRegistrationController::class,
                'store',
            ]);
            Route::get('/shop-pending', [
                TenantRegistrationController::class,
                'pending',
            ])->name('shop.pending');

            // Admin Portal
            Route::prefix('admin')->group(function () {
                // Guest
                Route::middleware('admin.guest')->group(function () {
                    Route::get('/login', [
                        AdminLoginController::class,
                        'create',
                    ])->name('admin.login');
                    Route::post('/login', [
                        AdminLoginController::class,
                        'store',
                    ]);
                    Route::get('/forgot-password', [
                        \App\Http\Controllers\Admin\Auth\ForgotPasswordController::class,
                        'create',
                    ])->name('admin.password.request');
                    Route::post('/forgot-password', [
                        \App\Http\Controllers\Admin\Auth\ForgotPasswordController::class,
                        'store',
                    ])->name('admin.password.email');
                    Route::get('/reset-password/{token}', [
                        \App\Http\Controllers\Admin\Auth\ResetPasswordController::class,
                        'create',
                    ])->name('admin.password.reset');
                    Route::post('/reset-password', [
                        \App\Http\Controllers\Admin\Auth\ResetPasswordController::class,
                        'store',
                    ])->name('admin.password.update');
                });

                // Authenticated
                Route::middleware('admin.auth')->group(function () {
                    Route::post('/logout', [
                        AdminLoginController::class,
                        'destroy',
                    ])->name('admin.logout');
                    Route::get('/dashboard', [
                        AdminDashboardController::class,
                        'index',
                    ])->name('admin.dashboard');

                    // Shop Registrations (admin)
                    Route::get('/registrations', [
                        RegistrationController::class,
                        'index',
                    ])->name('admin.registrations.index');
                    Route::post('/registrations/{registration}/approve', [
                        RegistrationController::class,
                        'approve',
                    ])
                        ->where('registration', '[0-9]+')
                        ->name('admin.registrations.approve');
                    Route::post('/registrations/{registration}/reject', [
                        RegistrationController::class,
                        'reject',
                    ])
                        ->where('registration', '[0-9]+')
                        ->name('admin.registrations.reject');

                    // Tenant Management
                    Route::get('/tenants', [
                        TenantController::class,
                        'index',
                    ])->name('admin.tenants.index');
                    Route::get('/tenants/{tenant}', [
                        TenantController::class,
                        'show',
                    ])->name('admin.tenants.show');
                    Route::patch('/tenants/{tenant}/toggle-status', [
                        TenantController::class,
                        'toggleStatus',
                    ])->name('admin.tenants.toggle-status');
                    Route::patch('/tenants/{tenant}/features', [
                        TenantController::class,
                        'updateFeatures',
                    ])->name('admin.tenants.update-features');
                    Route::patch('/tenants/{tenant}/plan', [
                        TenantController::class,
                        'updatePlan',
                    ])->name('admin.tenants.update-plan');
                    Route::patch('/tenants/{tenant}/mark-paid', [
                        TenantController::class,
                        'markPaid',
                    ])->name('admin.tenants.mark-paid');
                    Route::patch('/tenants/{tenant}/mark-unpaid', [
                        TenantController::class,
                        'markUnpaid',
                    ])->name('admin.tenants.mark-unpaid');
                    Route::delete('/tenants/{tenant}', [
                        TenantController::class,
                        'destroy',
                    ])->name('admin.tenants.destroy');

                    // Subscription Plans
                    Route::get('/subscription-plans', [
                        SubscriptionPlanController::class,
                        'index',
                    ])->name('admin.subscription-plans.index');
                    Route::get('/subscription-plans/create', [
                        SubscriptionPlanController::class,
                        'create',
                    ])->name('admin.subscription-plans.create');
                    Route::post('/subscription-plans', [
                        SubscriptionPlanController::class,
                        'store',
                    ])->name('admin.subscription-plans.store');
                    Route::get('/subscription-plans/{plan}/edit', [
                        SubscriptionPlanController::class,
                        'edit',
                    ])->name('admin.subscription-plans.edit');
                    Route::put('/subscription-plans/{plan}', [
                        SubscriptionPlanController::class,
                        'update',
                    ])->name('admin.subscription-plans.update');
                    Route::delete('/subscription-plans/{plan}', [
                        SubscriptionPlanController::class,
                        'destroy',
                    ])->name('admin.subscription-plans.destroy');

                    // Settings
                    Route::get('/settings', function () {
                        return redirect()->route('admin.settings.profile');
                    })->name('admin.settings.index');

                    // Profile
                    Route::get('/settings/profile', [
                        AdminProfileController::class,
                        'edit',
                    ])->name('admin.settings.profile');
                    Route::patch('/settings/profile', [
                        AdminProfileController::class,
                        'update',
                    ])->name('admin.settings.profile.update');
                    Route::put('/settings/password', [
                        AdminProfileController::class,
                        'updatePassword',
                    ])->name('admin.settings.password');

                    // Theme & Logo
                    Route::get('/settings/theme', [
                        AdminSettingsController::class,
                        'index',
                    ])->name('admin.settings.theme');
                    Route::patch('/settings/theme', [
                        AdminSettingsController::class,
                        'updateTheme',
                    ])->name('admin.settings.theme.update');
                    Route::post('/settings/logo', [
                        AdminSettingsController::class,
                        'updateLogo',
                    ])->name('admin.settings.logo');
                    Route::delete('/settings/logo', [
                        AdminSettingsController::class,
                        'removeLogo',
                    ])->name('admin.settings.logo.remove');
                });
            });
        });
}
