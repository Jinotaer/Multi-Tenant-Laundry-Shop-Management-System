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
    ],

];
