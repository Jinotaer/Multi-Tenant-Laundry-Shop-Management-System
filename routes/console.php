<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tenants:expire-trials')->daily();
Schedule::command('subscriptions:expire')->daily();
Schedule::command('app:sync-github-releases')->daily();

// Tenant metrics collection - runs daily at midnight
Schedule::command('tenants:collect-metrics')->daily();

// Reset monthly usage counters - runs on the first day of each month
Schedule::command('tenants:reset-usage')->monthlyOn(1, '00:00');
