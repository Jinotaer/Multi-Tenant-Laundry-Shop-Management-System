<?php

declare(strict_types=1);

/**
 * Standalone rollback: stops Apache, restores from the backup dir listed in
 * the config, runs cache rebuilds, and starts Apache. Used when an update
 * failed but automatic rollback inside apply.php did not complete.
 */

if ($argc < 2) {
    fwrite(STDERR, "rollback.php: missing config path argument\n");
    exit(2);
}

require __DIR__ . '/lib/StatusWriter.php';
require __DIR__ . '/lib/ApacheControl.php';
require __DIR__ . '/lib/FileSync.php';

$configPath = $argv[1];

if (! is_file($configPath)) {
    fwrite(STDERR, "rollback.php: config file not found [{$configPath}]\n");
    exit(2);
}

$config = json_decode((string) file_get_contents($configPath), true);
if (! is_array($config)) {
    fwrite(STDERR, "rollback.php: config file is not valid JSON\n");
    exit(2);
}

$status = new StatusWriter($config['status_file'], $config['log_file']);
$apache = new ApacheControl(
    $config['apache_service'] ?? null,
    $config['apache_start_bat'] ?? null,
    $status,
);
$sync = new FileSync($status);

$appRoot = rtrim($config['app_root'], "/\\");
$backupDir = rtrim($config['previous_backup_dir'], "/\\");
$manifest = $config['manifest'];

try {
    if (! is_dir($backupDir)) {
        throw new RuntimeException("Backup dir not found: {$backupDir}");
    }

    $status->update('start', 'Starting manual rollback', 'running', [
        'backup_dir' => $backupDir,
    ]);

    $apache->stop();

    $sync->sync($backupDir, $appRoot, $manifest, 'rollback');
    $status->update('swap', 'Files restored from backup');

    $php = $config['php_binary'] ?? 'php';
    foreach (['cache:clear', 'config:clear', 'route:clear', 'view:clear', 'config:cache', 'route:cache', 'view:cache'] as $sig) {
        $status->update('cache', "php artisan {$sig}");
        runShell([$php, 'artisan', $sig], $appRoot);
    }

    $apache->start();

    $status->finalize('rolled_back', 'Rollback completed successfully', [
        'backup_dir' => $backupDir,
    ]);
    exit(0);
} catch (Throwable $e) {
    $status->finalize('failed', 'Rollback failed: ' . $e->getMessage(), [
        'error' => $e->getMessage(),
    ]);

    // Best-effort: try to bring Apache back up even after rollback failure.
    try {
        $apache->start();
    } catch (Throwable) {
        // Nothing more we can do autonomously.
    }

    exit(1);
}

function runShell(array $args, string $cwd): void
{
    $command = implode(' ', array_map(static fn (string $a): string => escapeshellarg($a), $args));
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($command, $descriptors, $pipes, $cwd);
    if (! is_resource($process)) {
        throw new RuntimeException("Failed to launch [{$command}].");
    }
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]) ?: '';
    $err = stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);
    if ($exit !== 0) {
        throw new RuntimeException("[{$command}] failed (exit {$exit}): " . trim($out . "\n" . $err));
    }
}
