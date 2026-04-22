# System Updates Implementation Guide

## Overview
This document explains the tenant update system with backup, migration, optional code deployment, tenant-scoped maintenance mode, preflight validation, and optional smoke testing.

## Features Implemented

### 1. **Actual Backup Implementation** ✅
- **Service:** `App\Services\TenantBackupService`
- **Features:**
  - Database backup using mysqldump
  - File backup (tenant storage)
  - Compressed zip archives
  - Metadata tracking
  - Automatic cleanup (keeps last 10 backups)
  - Restore functionality

**Usage:**
```php
$backupService = app(TenantBackupService::class);
$result = $backupService->createBackup($tenant, 'manual');
```

### 2. **Migration Runner** ✅
- **Service:** `App\Services\TenantMigrationService`
- **Features:**
  - Version-specific migration execution
  - Migration rollback support
  - Pending migration detection
  - Migration status tracking

**Usage:**
```php
$migrationService = app(TenantMigrationService::class);
$result = $migrationService->runMigrationsForVersion($tenant, 'v1.0.0', 'v2.0.0');
```

### 3. **Code Deployment** ✅
- **Service:** `App\Services\CodeDeploymentService`
- **Features:**
  - Download releases from GitHub
  - Extract and deploy code
  - Backup before deployment
  - Run composer install
  - Run npm build
  - Cache clearing and rebuilding
  - Rollback capability

**Usage:**
```php
$deploymentService = app(CodeDeploymentService::class);
$result = $deploymentService->deployFromGitHub('v2.0.0');
```

### 4. **Tenant-Scoped Maintenance During Updates** ✅
- **Middleware:** `App\Http\Middleware\CheckTenantUpdateMaintenance`
- **Features:**
  - Marks only the updating tenant as in maintenance mode
  - Other tenants remain online and unaffected
  - Allows update center and logout routes while maintenance is active

### 5. **Preflight Validation + Optional Smoke Test** ✅
- **Preflight:** `CodeDeploymentService::canDeploy()`
- **Smoke Test:** Optional shell command configured via env
- **Features:**
  - Validate deployment prerequisites before pipeline execution
  - Run smoke test after pipeline and before final success response
  - Keep tenant maintenance mode active on failure for manual verification

## Configuration

### Environment Variables

Add to `.env`:
```env
# GitHub Configuration
GITHUB_REPO=username/repository
GITHUB_TOKEN=your_github_token

# Update System
AUTO_DEPLOY_CODE=false
ALLOW_TENANT_CODE_DEPLOY=false
REQUIRED_UPDATE_GRACE_PERIOD=7
AUTO_RUN_MIGRATIONS=true
MIGRATION_TIMEOUT=300

# Tenant update maintenance
TENANT_UPDATE_MAINTENANCE_ENABLED=true
TENANT_UPDATE_MAINTENANCE_TTL_MINUTES=60

# Optional smoke test
UPDATE_SMOKE_TEST_ENABLED=false
UPDATE_SMOKE_TEST_COMMAND="php artisan about"

# Backup Settings
BACKUP_RETENTION_COUNT=10

# Deployment Settings
MIN_DISK_SPACE_MB=500
BACKUP_BEFORE_DEPLOY=true
RUN_COMPOSER_INSTALL=true
RUN_NPM_BUILD=true
```

### Register Middleware

Alias is already registered in `bootstrap/app.php`:
```php
'tenant.update.maintenance' => \App\Http\Middleware\CheckTenantUpdateMaintenance::class,
```

And applied to tenant authenticated routes in `routes/tenant.php`.

## Update Process Flow

### Automatic Update Process:
1. **Preflight Validate** - Run `canDeploy()` checks (when code deployment is enabled)
2. **Backup Creation** - Full tenant database + files backup
3. **Enter Tenant Maintenance** - Only the current tenant is maintenance-blocked
4. **Code Deployment** (optional) - Download/extract/replace/install if allowed
5. **Migration Execution** - Run version-specific tenant migrations
6. **Optional Smoke Test** - Execute configured post-update command
7. **Version Update** - Update tenant version record
8. **Exit Tenant Maintenance on Success** - Maintenance cleared only after successful completion

### On Failure:
- Automatic rollback/restore where applicable
- Error logging
- User notification
- Tenant maintenance remains active for manual verification if failure occurred after maintenance began

## API Routes

```php
// View updates
GET /updates

// Apply update
POST /updates/{release}/apply

// Create manual backup
POST /updates/backup/create

// Restore from backup
POST /updates/backup/restore

// Run pending migrations
POST /updates/migrations/run
```

## Database Schema

### Required Columns in `app_releases`:
```sql
- is_required (boolean) - Mark as required update
```

### Migration:
```php
Schema::table('app_releases', function (Blueprint $table) {
    $table->boolean('is_required')->default(false)->after('is_prerelease');
});
```

## Usage Examples

### 1. Create Manual Backup
```php
Route::post('/backup', function() {
    $backupService = app(TenantBackupService::class);
    $result = $backupService->createBackup(tenant(), 'manual');
    
    if ($result['success']) {
        return "Backup created: {$result['backup_name']}";
    }
    
    return "Backup failed: {$result['error']}";
});
```

### 2. Apply Update with Full Process
```php
$updateController = app(UpdateController::class);
$release = AppRelease::where('version_tag', 'v2.0.0')->first();
$updateController->update(request(), $release);
```

### 3. List Tenant Backups
```php
$backupService = app(TenantBackupService::class);
$backups = $backupService->listBackups(tenant()->id);
```

## Security Considerations

1. **Backup Storage:** Backups stored in `storage/app/backups/tenants/`
2. **Permissions:** Only owners can manage updates
3. **Validation:** Safety checks before rollback
4. **Logging:** All operations logged
5. **Encryption:** Consider encrypting backup files

## Troubleshooting

### Backup Fails
- Check disk space
- Verify mysqldump is available
- Check database credentials
- Verify write permissions

### Migration Fails
- Check migration syntax
- Verify database connection
- Review migration logs
- Restore from backup if needed

### Deployment Fails
- Check GitHub token
- Verify repository access
- Check disk space
- Verify composer/npm availability

### Tenant Stuck in Maintenance After Failed Update
- Check update logs for the underlying error
- Verify backup restore status
- Re-run update after fixing root cause
- Clear tenant maintenance cache key manually only if recovery is complete

## Monitoring

### Log Files
- `storage/logs/laravel.log` - All update operations
- Check for: preflight checks, backup creation, maintenance start/end, migrations, deployments, smoke test

### Database Queries
```sql
-- Check tenant versions
SELECT t.id, ar.version_tag, tu.status, tu.action_taken_at
FROM tenants t
JOIN tenant_updates tu ON t.id = tu.tenant_id
JOIN app_releases ar ON tu.app_release_id = ar.id
WHERE tu.is_current = 1;

-- Check update history
SELECT * FROM tenant_updates ORDER BY created_at DESC LIMIT 50;
```

## Best Practices

1. **Always test updates in staging first**
2. **Create manual backup before major updates**
3. **Monitor disk space regularly**
4. **Keep backup retention reasonable (10 backups)**
5. **Use semantic versioning (v1.0.0, v2.0.0)**
6. **Document breaking changes in release notes**
7. **Keep `ALLOW_TENANT_CODE_DEPLOY=false` for tenant isolation**
8. **Enable a lightweight smoke test command in production**

## Maintenance

### Clean Old Backups
```php
$backupService = app(TenantBackupService::class);
// Automatically cleans old backups (keeps last 10)
```

### Sync GitHub Releases
```php
$githubService = app(GitHubReleaseService::class);
$githubService->syncReleases(force: true);
```

### Check System Health
```php
$deploymentService = app(CodeDeploymentService::class);
$health = $deploymentService->canDeploy();
```

## Support

For issues or questions:
1. Check logs in `storage/logs/`
2. Verify configuration in `config/updates.php`
3. Review database records in `tenant_updates`
4. Contact system administrator
