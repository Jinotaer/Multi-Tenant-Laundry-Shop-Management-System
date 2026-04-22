<?php

return [

    /*
    |--------------------------------------------------------------------------
    | GitHub Repository Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your GitHub repository for automatic release syncing.
    | Format: "username/repository"
    |
    */

    'github' => [
        'repo' => env('GITHUB_REPO', ''),
        'token' => env('GITHUB_TOKEN', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic Code Deployment
    |--------------------------------------------------------------------------
    |
    | Enable automatic code deployment from GitHub releases.
    | WARNING: This will overwrite your application files!
    |
    */

    'auto_deploy_code' => env('AUTO_DEPLOY_CODE', false),

    /*
    |--------------------------------------------------------------------------
    | Tenant Update Isolation
    |--------------------------------------------------------------------------
    |
    | Keep this disabled to ensure tenant-triggered updates do not replace
    | shared application code and impact other tenant stores.
    |
    */

    'allow_tenant_code_deploy' => env('ALLOW_TENANT_CODE_DEPLOY', false),

    /*
    |--------------------------------------------------------------------------
    | Tenant Update Maintenance Mode
    |--------------------------------------------------------------------------
    |
    | Enable a tenant-scoped maintenance state while updates are running.
    | This keeps other tenants unaffected.
    |
    */

    'tenant_maintenance' => [
        'enabled' => env('TENANT_UPDATE_MAINTENANCE_ENABLED', true),
        'ttl_minutes' => env('TENANT_UPDATE_MAINTENANCE_TTL_MINUTES', 60),
        'cache_store' => env('TENANT_UPDATE_MAINTENANCE_CACHE_STORE', 'file'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional Smoke Test
    |--------------------------------------------------------------------------
    |
    | Configure an optional command to verify the deployment after install.
    | Example: php artisan about
    |
    */

    'smoke_test' => [
        'enabled' => env('UPDATE_SMOKE_TEST_ENABLED', false),
        'command' => env('UPDATE_SMOKE_TEST_COMMAND', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Required Update Settings
    |--------------------------------------------------------------------------
    |
    | Configure grace period for required updates (in days).
    | After grace period expires, tenants will be forced to update.
    |
    */

    'required_update_grace_period' => env('REQUIRED_UPDATE_GRACE_PERIOD', 7),

    /*
    |--------------------------------------------------------------------------
    | Backup Settings
    |--------------------------------------------------------------------------
    |
    | Configure backup retention and storage settings.
    |
    */

    'backup' => [
        'retention_count' => env('BACKUP_RETENTION_COUNT', 10),
        'storage_path' => storage_path('app/backups/tenants'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Settings
    |--------------------------------------------------------------------------
    |
    | Configure migration behavior during updates.
    |
    */

    'migrations' => [
        'auto_run' => env('AUTO_RUN_MIGRATIONS', true),
        'timeout' => env('MIGRATION_TIMEOUT', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Deployment Settings
    |--------------------------------------------------------------------------
    |
    | Configure code deployment behavior.
    |
    */

    'deployment' => [
        'min_disk_space_mb' => env('MIN_DISK_SPACE_MB', 500),
        'backup_before_deploy' => env('BACKUP_BEFORE_DEPLOY', true),
        'run_composer_install' => env('RUN_COMPOSER_INSTALL', true),
        'run_npm_build' => env('RUN_NPM_BUILD', true),
        'run_database_migrations' => env('RUN_DATABASE_MIGRATIONS', true),
        'run_tenant_migrations' => env('RUN_TENANT_MIGRATIONS', true),
        'run_queue_restart' => env('RUN_QUEUE_RESTART', true),
    ],

];
