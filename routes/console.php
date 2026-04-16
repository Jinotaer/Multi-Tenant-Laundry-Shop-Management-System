<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tenants:expire-trials')->daily();
Schedule::command('subscriptions:expire')->daily();

// Auto-sync GitHub releases every 10 minutes
Schedule::command('releases:sync')->everyTenMinutes();

// Tenant metrics collection - runs daily at midnight
Schedule::command('tenants:collect-metrics')->daily();
