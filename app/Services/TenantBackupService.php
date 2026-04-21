<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;
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
        $connection = $this->getTenantDatabaseConnection($tenant);
        $driver = $connection['driver'] ?? null;
        $dbName = $connection['database'] ?? $tenant->database()->getName();
        $filename = "database_{$dbName}.sql";
        $filepath = "{$backupDir}/{$filename}";

        return match ($driver) {
            'mysql', 'mariadb' => $this->backupMySqlDatabase($connection, $dbName, $filepath),
            'sqlite' => $this->backupSqliteDatabase($connection, $filepath),
            default => throw new RuntimeException(
                "Database backup failed: Unsupported database driver [{$driver}]."
            ),
        };
    }

    /**
     * Backup tenant files (uploads, storage).
     */
    private function backupFiles($tenant, string $backupDir): ?string
    {
        $tenantStoragePath = $this->resolveTenantPublicStoragePath($tenant);

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
        $connection = $this->getTenantDatabaseConnection($tenant);
        $driver = $connection['driver'] ?? null;
        $dbName = $connection['database'] ?? $tenant->database()->getName();

        match ($driver) {
            'mysql', 'mariadb' => $this->restoreMySqlDatabase($connection, $dbName, $sqlFile),
            'sqlite' => $this->restoreSqliteDatabase($connection, $sqlFile),
            default => throw new RuntimeException(
                "Database restore failed: Unsupported database driver [{$driver}]."
            ),
        };
    }

    /**
     * Restore tenant files.
     */
    private function restoreFiles($tenant, string $filesPath): void
    {
        $tenantStoragePath = $this->resolveTenantPublicStoragePath($tenant);

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

    /**
     * Get the resolved tenant database connection config.
     */
    private function getTenantDatabaseConnection($tenant): array
    {
        return $tenant->database()->connection();
    }

    /**
     * Backup a MySQL/MariaDB tenant database.
     */
    private function backupMySqlDatabase(array $connection, string $dbName, string $filepath): string
    {
        $command = $this->buildMySqlCommand('mysqldump', $connection, $dbName, [
            '--single-transaction',
            '--skip-lock-tables',
            '--default-character-set=' . ($connection['charset'] ?? 'utf8mb4'),
        ]);

        $result = $this->runProcess($command, $filepath);

        if ($result['exit_code'] !== 0) {
            File::delete($filepath);

            throw new RuntimeException($this->formatDatabaseProcessError(
                'Database backup failed',
                $result
            ));
        }

        return $filepath;
    }

    /**
     * Restore a MySQL/MariaDB tenant database.
     */
    private function restoreMySqlDatabase(array $connection, string $dbName, string $sqlFile): void
    {
        $command = $this->buildMySqlCommand('mysql', $connection, $dbName);
        $result = $this->runProcess($command, null, $sqlFile);

        if ($result['exit_code'] !== 0) {
            throw new RuntimeException($this->formatDatabaseProcessError(
                'Database restore failed',
                $result
            ));
        }
    }

    /**
     * Backup a SQLite tenant database by copying the database file.
     */
    private function backupSqliteDatabase(array $connection, string $filepath): string
    {
        $databasePath = $connection['database'] ?? null;

        if (!$databasePath || $databasePath === ':memory:' || !File::exists($databasePath)) {
            throw new RuntimeException('Database backup failed: SQLite database file could not be found.');
        }

        File::copy($databasePath, $filepath);

        return $filepath;
    }

    /**
     * Restore a SQLite tenant database by replacing the database file.
     */
    private function restoreSqliteDatabase(array $connection, string $sqlFile): void
    {
        $databasePath = $connection['database'] ?? null;

        if (!$databasePath || $databasePath === ':memory:') {
            throw new RuntimeException('Database restore failed: SQLite database file could not be resolved.');
        }

        File::ensureDirectoryExists(dirname($databasePath));
        File::copy($sqlFile, $databasePath);
    }

    /**
     * Build a MySQL client command from the tenant connection config.
     */
    private function buildMySqlCommand(
        string $binary,
        array $connection,
        string $dbName,
        array $extraArguments = []
    ): array {
        $command = [
            $this->resolveDatabaseBinary($binary),
            '--host=' . ($connection['host'] ?? '127.0.0.1'),
            '--user=' . ($connection['username'] ?? 'root'),
        ];

        if (!empty($connection['port'])) {
            $command[] = '--port=' . $connection['port'];
        }

        if (!empty($connection['unix_socket'])) {
            $command[] = '--socket=' . $connection['unix_socket'];
        }

        if (($connection['password'] ?? '') !== '') {
            $command[] = '--password=' . $connection['password'];
        }

        return [...$command, ...$extraArguments, $dbName];
    }

    /**
     * Resolve a database client binary path, including common Windows/XAMPP locations.
     */
    private function resolveDatabaseBinary(string $binary): string
    {
        $envKey = strtoupper($binary) . '_PATH';
        $windowsBinary = "{$binary}.exe";
        $xamppRoot = dirname(dirname(base_path()));

        $candidates = array_filter([
            env($envKey),
            PHP_OS_FAMILY === 'Windows' ? $xamppRoot . DIRECTORY_SEPARATOR . 'mysql' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $windowsBinary : null,
            PHP_OS_FAMILY === 'Windows' ? 'C:\\xampp\\mysql\\bin\\' . $windowsBinary : null,
        ]);

        foreach ($candidates as $candidate) {
            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        return PHP_OS_FAMILY === 'Windows' ? $windowsBinary : $binary;
    }

    /**
     * Run a database client process while capturing stderr.
     */
    private function runProcess(array $command, ?string $stdoutFile = null, ?string $stdinFile = null): array
    {
        $descriptorSpec = [
            0 => $stdinFile ? ['file', $stdinFile, 'r'] : ['pipe', 'r'],
            1 => $stdoutFile ? ['file', $stdoutFile, 'w'] : ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $pipes = [];
        $process = @proc_open($command, $descriptorSpec, $pipes, base_path());

        if (!is_resource($process)) {
            $binary = $command[0] ?? 'database client';

            throw new RuntimeException(
                "Failed to start {$binary}. Set " . strtoupper(pathinfo($binary, PATHINFO_FILENAME)) . '_PATH if it is installed outside PATH.'
            );
        }

        if (!$stdinFile && isset($pipes[0])) {
            fclose($pipes[0]);
        }

        $stdout = '';
        if (!$stdoutFile && isset($pipes[1])) {
            $stdout = stream_get_contents($pipes[1]) ?: '';
            fclose($pipes[1]);
        }

        $stderr = isset($pipes[2]) ? stream_get_contents($pipes[2]) ?: '' : '';
        if (isset($pipes[2])) {
            fclose($pipes[2]);
        }

        $exitCode = proc_close($process);

        return [
            'exit_code' => $exitCode,
            'stdout' => trim($stdout),
            'stderr' => trim($stderr),
        ];
    }

    /**
     * Build a readable database client error message.
     */
    private function formatDatabaseProcessError(string $prefix, array $result): string
    {
        $details = $result['stderr'] ?: $result['stdout'] ?: "Command exited with code {$result['exit_code']}.";

        return "{$prefix}: {$details}";
    }

    /**
     * Resolve the tenant-scoped public storage path.
     */
    private function resolveTenantPublicStoragePath($tenant): string
    {
        if (tenancy()->initialized && tenant()?->getTenantKey() === $tenant->getTenantKey()) {
            return storage_path('app/public');
        }

        $previousTenant = tenancy()->initialized ? tenant() : null;

        tenancy()->initialize($tenant);

        try {
            return storage_path('app/public');
        } finally {
            if ($previousTenant) {
                tenancy()->initialize($previousTenant);
            } else {
                tenancy()->end();
            }
        }
    }
}
