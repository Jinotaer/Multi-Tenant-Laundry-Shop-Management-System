<?php

use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\AuthenticateTenant;
use App\Http\Middleware\CheckPlanLimit;
use App\Http\Middleware\CheckTenantFeature;
use App\Http\Middleware\CheckTenantUpdateMaintenance;
use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsStaff;
use App\Http\Middleware\RedirectIfAuthenticatedAdmin;
use App\Http\Middleware\TrackTenantUsage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            if (tenant()) {
                return route('tenant.dashboard');
            }

            if ($request->routeIs('admin.*')) {
                return route('admin.dashboard');
            }

            return '/';
        });

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'permission' => EnsureUserHasPermission::class,
            'staff' => EnsureUserIsStaff::class,
            'admin.auth' => AuthenticateAdmin::class,
            'admin.guest' => RedirectIfAuthenticatedAdmin::class,
            'tenant.auth' => AuthenticateTenant::class,
            'feature' => CheckTenantFeature::class,
            'plan.limit' => CheckPlanLimit::class,
            'track.usage' => TrackTenantUsage::class,
            'tenant.update.maintenance' => CheckTenantUpdateMaintenance::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Not found.'], 404);
            }
            return response()->view('errors.404', [], 404);
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }
            if (config('app.debug')) {
                return null;
            }
            return response()->view('errors.500', [], 500);
        });
    })->create();
