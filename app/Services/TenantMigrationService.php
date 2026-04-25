<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class TenantMigrationService
{
    /**
     * Run migrations for a specific version.
     */
    public function runMigrationsForVersion($tenant, string $fromVersion, string $toVersion): array
    {
        return $this->withTenantContext($tenant, function () use ($tenant, $fromVersion, $toVersion) {
            $results = [];

            try {
                // During forward updates, run any pending version-tagged tenant
                // migrations up to the target version. This backfills migrations
                // that may have been introduced late or skipped on an earlier
                // release without forcing operators to run commands manually.
                $migrations = $this->getPendingForwardMigrations($fromVersion, $toVersion);

                if (empty($migrations)) {
                    return [
                        'success' => true,
                        'message' => 'No migrations to run',
                        'migrations' => []
                    ];
                }

                // Run each migration
                foreach ($migrations as $migration) {
                    try {
                        $this->runSingleMigration($migration);
                        $results[] = [
                            'migration' => $migration,
                            'status' => 'success'
                        ];
                    } catch (\Exception $e) {
                        $results[] = [
                            'migration' => $migration,
                            'status' => 'failed',
                            'error' => $e->getMessage()
                        ];
                        throw $e; // Stop on first failure
                    }
                }

                Log::info("Migrations completed for tenant {$tenant->id}", [
                    'from_version' => $fromVersion,
                    'to_version' => $toVersion,
                    'migrations_run' => count($results)
                ]);

                return [
                    'success' => true,
                    'migrations' => $results
                ];

            } catch (\Exception $e) {
                Log::error("Migration failed for tenant {$tenant->id}", [
                    'error' => $e->getMessage(),
                    'from_version' => $fromVersion,
                    'to_version' => $toVersion
                ]);

                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'migrations' => $results
                ];
            }
        });
    }

    /**
     * Get migrations between two versions.
     */
    private function getMigrationsBetweenVersions(string $fromVersion, string $toVersion): array
    {
        $allMigrations = $this->allTenantMigrationFiles();

        // When versions are identical, this is a "run pending" operation,
        // so include all tenant migration files.
        if (ltrim($fromVersion, 'v') === ltrim($toVersion, 'v')) {
            return $allMigrations;
        }
        
        // Get version-specific migrations
        $versionMigrations = $this->filterMigrationsByVersion($allMigrations, $fromVersion, $toVersion);
        
        return $versionMigrations;
    }

    /**
     * Get all pending tenant migrations that should exist by the target version.
     */
    private function getPendingForwardMigrations(string $fromVersion, string $toVersion): array
    {
        $allMigrations = $this->allTenantMigrationFiles();

        if (ltrim($fromVersion, 'v') === ltrim($toVersion, 'v')) {
            return $allMigrations;
        }

        return array_values(array_filter($allMigrations, function (string $migration) use ($toVersion): bool {
            $migrationVersion = $this->extractVersionFromMigration($migration);

            if ($migrationVersion === null) {
                return false;
            }

            return version_compare(ltrim($migrationVersion, 'v'), ltrim($toVersion, 'v'), '<=')
                && ! $this->hasMigrationAlreadyRun($migration);
        }));
    }

    /**
     * Filter migrations by version tags in filename or comments.
     */
    private function filterMigrationsByVersion(array $migrations, string $fromVersion, string $toVersion): array
    {
        return array_filter($migrations, function($migration) use ($fromVersion, $toVersion) {
            $migrationVersion = $this->extractVersionFromMigration($migration);

            if ($migrationVersion !== null) {
                return $this->isVersionBetween($migrationVersion, $fromVersion, $toVersion);
            }
            
            // Ignore untagged migrations for cross-version updates/rollbacks to avoid
            // touching foundational schema migrations unrelated to the target version delta.
            return false;
        });
    }

    /**
     * Check if version is between two versions.
     */
    private function isVersionBetween(string $version, string $from, string $to): bool
    {
        $v = ltrim($version, 'v');
        $f = ltrim($from, 'v');
        $t = ltrim($to, 'v');
        
        return version_compare($v, $f, '>') && version_compare($v, $t, '<=');
    }

    /**
     * Run a single migration file.
     */
    private function runSingleMigration(string $migrationFile): void
    {
        if ($this->hasMigrationAlreadyRun($migrationFile)) {
            return;
        }
        
        // Run only the targeted migration file.
        Artisan::call('migrate', [
            '--path' => 'database/migrations/tenant/' . $migrationFile,
            '--force' => true,
            '--step' => true
        ]);
    }

    /**
     * Return all tenant migration filenames in sorted order.
     */
    private function allTenantMigrationFiles(): array
    {
        $migrationsPath = database_path('migrations/tenant');

        if (! File::exists($migrationsPath)) {
            return [];
        }

        return collect(File::files($migrationsPath))
            ->map(fn ($file) => $file->getFilename())
            ->filter(fn ($file) => str_ends_with($file, '.php'))
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Extract a semantic version from a tagged migration filename.
     */
    private function extractVersionFromMigration(string $migration): ?string
    {
        if (! preg_match('/v(\d+)_(\d+)_(\d+)/', $migration, $matches)) {
            return null;
        }

        return "v{$matches[1]}.{$matches[2]}.{$matches[3]}";
    }

    /**
     * Determine whether a tenant migration has already been recorded.
     */
    private function hasMigrationAlreadyRun(string $migrationFile): bool
    {
        $migrationName = str_replace('.php', '', $migrationFile);

        return DB::table('migrations')
            ->where('migration', $migrationName)
            ->exists();
    }

    /**
     * Rollback migrations for a version downgrade.
     */
    public function rollbackMigrationsForVersion($tenant, string $fromVersion, string $toVersion): array
    {
        return $this->withTenantContext($tenant, function () use ($tenant, $fromVersion, $toVersion) {
            try {
                // Get migrations to rollback
                $migrations = $this->getMigrationsBetweenVersions($toVersion, $fromVersion);

                if (empty($migrations)) {
                    return [
                        'success' => true,
                        'message' => 'No migrations to rollback'
                    ];
                }

                // Rollback migrations
                $steps = count($migrations);
                Artisan::call('migrate:rollback', [
                    '--path' => 'database/migrations/tenant',
                    '--step' => $steps,
                    '--force' => true
                ]);

                Log::info("Migrations rolled back for tenant {$tenant->id}", [
                    'from_version' => $fromVersion,
                    'to_version' => $toVersion,
                    'steps' => $steps
                ]);

                return [
                    'success' => true,
                    'migrations_rolled_back' => $steps
                ];

            } catch (\Exception $e) {
                Log::error("Migration rollback failed for tenant {$tenant->id}", [
                    'error' => $e->getMessage()
                ]);

                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Check if migrations are pending.
     */
    public function hasPendingMigrations($tenant): bool
    {
        return $this->withTenantContext($tenant, function () {
            Artisan::call('migrate:status', [
                '--path' => 'database/migrations/tenant'
            ]);

            return str_contains(Artisan::output(), 'Pending');
        });
    }

    /**
     * Get migration status.
     */
    public function getMigrationStatus($tenant): array
    {
        return $this->withTenantContext($tenant, function () {
            try {
                Artisan::call('migrate:status', [
                    '--path' => 'database/migrations/tenant'
                ]);

                return [
                    'success' => true,
                    'output' => Artisan::output()
                ];

            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Run a callback without dropping the caller's tenant context.
     */
    private function withTenantContext($tenant, callable $callback): mixed
    {
        if (tenancy()->initialized && tenant()?->getTenantKey() === $tenant->getTenantKey()) {
            return $callback();
        }

        $previousTenant = tenancy()->initialized ? tenant() : null;

        tenancy()->initialize($tenant);

        try {
            return $callback();
        } finally {
            if ($previousTenant) {
                tenancy()->initialize($previousTenant);
            } else {
                tenancy()->end();
            }
        }
    }
}
