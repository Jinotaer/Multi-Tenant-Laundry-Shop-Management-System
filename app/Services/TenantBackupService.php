<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class TenantBackupService
{
    private string $backupPath;

    public function __construct()
    {
        $this->backupPath = storage_path('app/backups/tenants');
        
        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    /**
     * Create a full backup for a tenant (database + files).
     */
    public function createBackup($tenant, string $reason = 'manual'): array
    {
        $timestamp = now()->format('Y-m-d_His');
        $backupName = "tenant_{$tenant->id}_{$timestamp}_{$reason}";
        
        try {
            // Create backup directory
            $backupDir = "{$this->backupPath}/{$backupName}";
            File::makeDirectory($backupDir, 0755, true);

            // Backup database
            $dbBackupPath = $this->backupDatabase($tenant, $backupDir);
            
            // Backup tenant files
            $filesBackupPath = $this->backupFiles($tenant, $backupDir);
            
            // Create metadata
            $metadata = $this->createMetadata($tenant, $reason, $dbBackupPath, $filesBackupPath);
            File::put("{$backupDir}/metadata.json", json_encode($metadata, JSON_PRETTY_PRINT));
            
            // Compress backup
            $zipPath = $this->compressBackup($backupDir, $backupName);
            
            // Clean up uncompressed files
            File::deleteDirectory($backupDir);
            
            // Clean old backups (keep last 10)
            $this->cleanOldBackups($tenant->id);
            
            Log::info("Backup created successfully for tenant {$tenant->id}", [
                'backup_file' => $zipPath,
                'reason' => $reason
            ]);
            
            return [
                'success' => true,
                'backup_path' => $zipPath,
                'backup_name' => $backupName,
                'size' => File::size($zipPath)
            ];
            
        } catch (\Exception $e) {
            Log::error("Backup failed for tenant {$tenant->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Backup tenant database.
     */
    private function backupDatabase($tenant, string $backupDir): string
    {
        $dbName = $tenant->tenancy_db_name;
        $filename = "database_{$dbName}.sql";
        $filepath = "{$backupDir}/{$filename}";
        
        $host = config('tenancy.database.host', '127.0.0.1');
        $username = config('tenancy.database.username', 'root');
        $password = config('tenancy.database.password', '');
        
        // Use mysqldump
        $command = sprintf(
            'mysqldump --host=%s --user=%s %s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($username),
            $password ? '--password=' . escapeshellarg($password) : '',
            escapeshellarg($dbName),
            escapeshellarg($filepath)
        );
        
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            throw new \Exception("Database backup failed: " . implode("\n", $output));
        }
        
        return $filepath;
    }

    /**
     * Backup tenant files (uploads, storage).
     */
    private function backupFiles($tenant, string $backupDir): ?string
    {
        $tenantStoragePath = storage_path("app/public/tenant_{$tenant->id}");
        
        if (!File::exists($tenantStoragePath)) {
            return null;
        }
        
        $filesBackupPath = "{$backupDir}/files";
        File::copyDirectory($tenantStoragePath, $filesBackupPath);
        
        return $filesBackupPath;
    }

    /**
     * Create backup metadata.
     */
    private function createMetadata($tenant, string $reason, string $dbPath, ?string $filesPath): array
    {
        return [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->id,
            'backup_date' => now()->toIso8601String(),
            'reason' => $reason,
            'current_version' => $tenant->currentVersion(),
            'database_backup' => basename($dbPath),
            'files_backup' => $filesPath ? 'files' : null,
            'database_size' => File::size($dbPath),
            'files_size' => $filesPath ? $this->getDirectorySize($filesPath) : 0,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version()
        ];
    }

    /**
     * Compress backup directory to zip.
     */
    private function compressBackup(string $backupDir, string $backupName): string
    {
        $zipPath = "{$this->backupPath}/{$backupName}.zip";
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Failed to create zip archive");
        }
        
        $files = File::allFiles($backupDir);
        
        foreach ($files as $file) {
            $relativePath = str_replace($backupDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $zip->addFile($file->getPathname(), $relativePath);
        }
        
        $zip->close();
        
        return $zipPath;
    }

    /**
     * Restore tenant from backup.
     */
    public function restoreBackup($tenant, string $backupPath): array
    {
        try {
            // Extract backup
            $extractPath = "{$this->backupPath}/restore_temp_" . time();
            $zip = new ZipArchive();
            
            if ($zip->open($backupPath) !== true) {
                throw new \Exception("Failed to open backup file");
            }
            
            $zip->extractTo($extractPath);
            $zip->close();
            
            // Read metadata
            $metadata = json_decode(File::get("{$extractPath}/metadata.json"), true);
            
            // Restore database
            $this->restoreDatabase($tenant, "{$extractPath}/{$metadata['database_backup']}");
            
            // Restore files
            if ($metadata['files_backup']) {
                $this->restoreFiles($tenant, "{$extractPath}/{$metadata['files_backup']}");
            }
            
            // Clean up
            File::deleteDirectory($extractPath);
            
            Log::info("Backup restored successfully for tenant {$tenant->id}");
            
            return ['success' => true, 'metadata' => $metadata];
            
        } catch (\Exception $e) {
            Log::error("Restore failed for tenant {$tenant->id}", ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Restore database from SQL file.
     */
    private function restoreDatabase($tenant, string $sqlFile): void
    {
        $dbName = $tenant->tenancy_db_name;
        $host = config('tenancy.database.host', '127.0.0.1');
        $username = config('tenancy.database.username', 'root');
        $password = config('tenancy.database.password', '');
        
        $command = sprintf(
            'mysql --host=%s --user=%s %s %s < %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($username),
            $password ? '--password=' . escapeshellarg($password) : '',
            escapeshellarg($dbName),
            escapeshellarg($sqlFile)
        );
        
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            throw new \Exception("Database restore failed: " . implode("\n", $output));
        }
    }

    /**
     * Restore tenant files.
     */
    private function restoreFiles($tenant, string $filesPath): void
    {
        $tenantStoragePath = storage_path("app/public/tenant_{$tenant->id}");
        
        if (File::exists($tenantStoragePath)) {
            File::deleteDirectory($tenantStoragePath);
        }
        
        File::copyDirectory($filesPath, $tenantStoragePath);
    }

    /**
     * Clean old backups, keep last N backups.
     */
    private function cleanOldBackups(string $tenantId, int $keepCount = 10): void
    {
        $backups = collect(File::files($this->backupPath))
            ->filter(fn($file) => str_contains($file->getFilename(), "tenant_{$tenantId}_"))
            ->sortByDesc(fn($file) => $file->getMTime())
            ->skip($keepCount);
        
        foreach ($backups as $backup) {
            File::delete($backup->getPathname());
        }
    }

    /**
     * Get directory size in bytes.
     */
    private function getDirectorySize(string $path): int
    {
        $size = 0;
        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    /**
     * List all backups for a tenant.
     */
    public function listBackups(string $tenantId): array
    {
        return collect(File::files($this->backupPath))
            ->filter(fn($file) => str_contains($file->getFilename(), "tenant_{$tenantId}_"))
            ->map(function($file) {
                return [
                    'filename' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'size' => $file->getSize(),
                    'created_at' => $file->getMTime()
                ];
            })
            ->sortByDesc('created_at')
            ->values()
            ->toArray();
    }
}
