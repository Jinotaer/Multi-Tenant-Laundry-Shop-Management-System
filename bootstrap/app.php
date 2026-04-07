<?php

use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\AuthenticateTenant;
use App\Http\Middleware\CheckPlanLimit;
use App\Http\Middleware\CheckTenantFeature;
use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\RedirectIfAuthenticatedAdmin;
use App\Http\Middleware\TrackTenantUsage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'permission' => EnsureUserHasPermission::class,
            'admin.auth' => AuthenticateAdmin::class,
            'admin.guest' => RedirectIfAuthenticatedAdmin::class,
            'tenant.auth' => AuthenticateTenant::class,
            'feature' => CheckTenantFeature::class,
            'plan.limit' => CheckPlanLimit::class,
            'track.usage' => TrackTenantUsage::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
