# System Updates Implementation Guide

## Overview
This document explains the fully implemented tenant update system with backup, migration, code deployment, and forced updates.

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

### 4. **Forced Updates** ✅
- **Middleware:** `App\Http\Middleware\CheckRequiredUpdate`
- **Features:**
  - Grace period (default 7 days)
  - Automatic blocking after grace period
  - Warning messages during grace period
  - Allowed routes (update center, logout)

## Configuration

### Environment Variables

Add to `.env`:
```env
# GitHub Configuration
GITHUB_REPO=username/repository
GITHUB_TOKEN=your_github_token

# Update System
AUTO_DEPLOY_CODE=false
REQUIRED_UPDATE_GRACE_PERIOD=7
AUTO_RUN_MIGRATIONS=true
MIGRATION_TIMEOUT=300

# Backup Settings
BACKUP_RETENTION_COUNT=10

# Deployment Settings
MIN_DISK_SPACE_MB=500
BACKUP_BEFORE_DEPLOY=true
RUN_COMPOSER_INSTALL=true
RUN_NPM_BUILD=true
```

### Register Middleware

Add to `app/Http/Kernel.php`:
```php
protected $middlewareGroups = [
    'tenant.auth' => [
        // ... other middleware
        \App\Http\Middleware\CheckRequiredUpdate::class,
    ],
];
```

## Update Process Flow

### Automatic Update Process:
1. **Backup Creation** - Full database + files backup
2. **Code Deployment** (if enabled) - Download and deploy from GitHub
3. **Migration Execution** - Run version-specific migrations
4. **Version Update** - Update tenant version record
5. **Cache Rebuild** - Clear and rebuild all caches

### On Failure:
- Automatic rollback to backup
- Error logging
- User notification

## API Routes

```php
// View updates
GET /updates

// Apply update
POST /updates/{release}/apply

// Rollback version
POST /updates/{release}/rollback

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

### 3. Mark Release as Required
```php
$release = AppRelease::where('version_tag', 'v2.0.0')->first();
$release->update(['is_required' => true]);
```

### 4. List Tenant Backups
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

### Required Update Not Enforcing
- Verify middleware is registered
- Check `is_required` flag on release
- Verify grace period configuration

## Monitoring

### Log Files
- `storage/logs/laravel.log` - All update operations
- Check for: backup creation, migrations, deployments

### Database Queries
```sql
-- Check tenant versions
SELECT t.id, ar.version_tag, tu.status, tu.action_taken_at
FROM tenants t
JOIN tenant_updates tu ON t.id = tu.tenant_id
JOIN app_releases ar ON tu.app_release_id = ar.id
WHERE tu.is_current = 1;

-- Check required updates
SELECT * FROM app_releases WHERE is_required = 1;

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
7. **Set reasonable grace periods (7-14 days)**
8. **Test rollback procedures regularly**

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
