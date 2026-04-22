<?php

declare(strict_types=1);

/**
 * Laundry Shop Management — external updater orchestrator.
 *
 * Runs outside Apache (after apply.bat has stopped the web server) so PHP
 * files can be safely overwritten without Windows file-lock errors. All
 * Laravel dependencies are avoided — this script uses plain PHP only.
 *
 * Usage:  php apply.php <config-json-path>
 * Exit:   0 success, non-zero failure (rollback attempted automatically).
 */

if ($argc < 2) {
    fwrite(STDERR, "apply.php: missing config path argument\n");
    exit(2);
}

require __DIR__ . '/lib/StatusWriter.php';
require __DIR__ . '/lib/ApacheControl.php';
require __DIR__ . '/lib/FileSync.php';
require __DIR__ . '/lib/Updater.php';

$configPath = $argv[1];

if (! is_file($configPath)) {
    fwrite(STDERR, "apply.php: config file not found [{$configPath}]\n");
    exit(2);
}

$configRaw = file_get_contents($configPath);
$config = json_decode($configRaw, true);

if (! is_array($config)) {
    fwrite(STDERR, "apply.php: config file is not valid JSON\n");
    exit(2);
}

$updater = new Updater($config);

try {
    $updater->run();
    exit(0);
} catch (Throwable $e) {
    $updater->handleFailure($e);
    exit(1);
}
