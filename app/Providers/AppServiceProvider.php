<?php

namespace App\Providers;

use App\Models\TenantRegistration;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Explicit route model binding for admin registration routes
        Route::model('registration', TenantRegistration::class);

        Blade::if('feature', function (string $feature): bool {
            $tenant = tenant();

            return $tenant && $tenant->hasFeature($feature);
        });
    }
}
