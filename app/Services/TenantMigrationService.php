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
        $results = [];
        
        try {
            // Switch to tenant context
            tenancy()->initialize($tenant);
            
            // Get migrations between versions
            $migrations = $this->getMigrationsBetweenVersions($fromVersion, $toVersion);
            
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
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Get migrations between two versions.
     */
    private function getMigrationsBetweenVersions(string $fromVersion, string $toVersion): array
    {
        $migrationsPath = database_path('migrations/tenant');
        
        if (!File::exists($migrationsPath)) {
            return [];
        }
        
        $allMigrations = collect(File::files($migrationsPath))
            ->map(fn($file) => $file->getFilename())
            ->filter(fn($file) => str_ends_with($file, '.php'))
            ->sort()
            ->values()
            ->toArray();
        
        // Get version-specific migrations
        $versionMigrations = $this->filterMigrationsByVersion($allMigrations, $fromVersion, $toVersion);
        
        return $versionMigrations;
    }

    /**
     * Filter migrations by version tags in filename or comments.
     */
    private function filterMigrationsByVersion(array $migrations, string $fromVersion, string $toVersion): array
    {
        // For now, return all pending migrations
        // You can implement version-specific filtering based on migration naming convention
        // Example: 2024_01_01_000000_v2_0_0_add_new_feature.php
        
        return array_filter($migrations, function($migration) use ($fromVersion, $toVersion) {
            // Extract version from migration filename if present
            if (preg_match('/v(\d+)_(\d+)_(\d+)/', $migration, $matches)) {
                $migrationVersion = "v{$matches[1]}.{$matches[2]}.{$matches[3]}";
                return $this->isVersionBetween($migrationVersion, $fromVersion, $toVersion);
            }
            
            // If no version tag, include all pending migrations
            return true;
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
        $migrationName = str_replace('.php', '', $migrationFile);
        
        // Check if already run
        $alreadyRun = DB::table('migrations')
            ->where('migration', $migrationName)
            ->exists();
        
        if ($alreadyRun) {
            return;
        }
        
        // Run the migration
        Artisan::call('migrate', [
            '--path' => 'database/migrations/tenant',
            '--force' => true,
            '--step' => true
        ]);
    }

    /**
     * Rollback migrations for a version downgrade.
     */
    public function rollbackMigrationsForVersion($tenant, string $fromVersion, string $toVersion): array
    {
        try {
            tenancy()->initialize($tenant);
            
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
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Check if migrations are pending.
     */
    public function hasPendingMigrations($tenant): bool
    {
        try {
            tenancy()->initialize($tenant);
            
            $exitCode = Artisan::call('migrate:status', [
                '--path' => 'database/migrations/tenant'
            ]);
            
            $output = Artisan::output();
            
            return str_contains($output, 'Pending');
            
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Get migration status.
     */
    public function getMigrationStatus($tenant): array
    {
        try {
            tenancy()->initialize($tenant);
            
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
        } finally {
            tenancy()->end();
        }
    }
}
