<?php

declare(strict_types=1);

/**
 * Manifest-based file sync for the updater.
 *
 * Mirrors the logic in App\Services\CodeDeploymentService::syncCodeTree but
 * runs standalone (no Laravel). Only touches the top-level entries listed in
 * the manifest — .env, storage/, vendor/, and other runtime-owned paths are
 * untouched.
 */
final class FileSync
{
    public function __construct(private StatusWriter $status) {}

    /**
     * @param array{directories: array<int, string>, files: array<int, string>} $manifest
     */
    public function sync(string $sourceRoot, string $destinationRoot, array $manifest, string $operation): void
    {
        foreach ($manifest['directories'] as $dir) {
            $source = $this->join($sourceRoot, $dir);
            $destination = $this->join($destinationRoot, $dir);

            if (! is_dir($source)) {
                continue;
            }

            $this->status->update('file_sync', "Syncing directory [{$dir}]");
            $this->syncDirectory($source, $destination, $dir, $operation);
        }

        foreach ($manifest['files'] as $file) {
            $source = $this->join($sourceRoot, $file);
            $destination = $this->join($destinationRoot, $file);

            if (! is_file($source)) {
                continue;
            }

            $this->status->update('file_sync', "Syncing file [{$file}]");
            $this->copyFile($source, $destination, $file, $operation);
        }
    }

    /**
     * Back up top-level manifest entries before they are replaced. Returns the
     * absolute backup path so the caller can restore on failure.
     */
    public function backup(string $liveRoot, string $backupRoot, array $manifest): string
    {
        if (! is_dir($backupRoot)) {
            if (! @mkdir($backupRoot, 0755, true) && ! is_dir($backupRoot)) {
                throw new RuntimeException("Failed to create backup dir [{$backupRoot}].");
            }
        }

        foreach ($manifest['directories'] as $dir) {
            $source = $this->join($liveRoot, $dir);
            if (! is_dir($source)) {
                continue;
            }

            $this->status->update('backup', "Backing up directory [{$dir}]");
            $this->copyDirectory($source, $this->join($backupRoot, $dir));
        }

        foreach ($manifest['files'] as $file) {
            $source = $this->join($liveRoot, $file);
            if (! is_file($source)) {
                continue;
            }

            $this->status->update('backup', "Backing up file [{$file}]");
            $this->copyFile($source, $this->join($backupRoot, $file), $file, 'backup');
        }

        return $backupRoot;
    }

    private function syncDirectory(string $source, string $destination, string $relPath, string $operation): void
    {
        if (is_file($destination)) {
            $this->deletePath($destination, $relPath, $operation);
        }

        if (! is_dir($destination)) {
            if (! @mkdir($destination, 0755, true) && ! is_dir($destination)) {
                throw new RuntimeException("Failed to create directory [{$destination}] during {$operation}.");
            }
        }

        foreach ($this->listDirectory($source) as $entry) {
            $sourcePath = $this->join($source, $entry);
            $destinationPath = $this->join($destination, $entry);
            $entryRel = trim($relPath . '/' . $entry, '/');

            if (is_dir($sourcePath)) {
                if (is_file($destinationPath)) {
                    $this->deletePath($destinationPath, $entryRel, $operation);
                }
                $this->syncDirectory($sourcePath, $destinationPath, $entryRel, $operation);
                continue;
            }

            if (is_dir($destinationPath)) {
                $this->deletePath($destinationPath, $entryRel, $operation);
            }

            $this->copyFile($sourcePath, $destinationPath, $entryRel, $operation);
        }

        foreach ($this->listDirectory($destination) as $entry) {
            $sourcePath = $this->join($source, $entry);
            if (file_exists($sourcePath)) {
                continue;
            }

            $destinationPath = $this->join($destination, $entry);
            $entryRel = trim($relPath . '/' . $entry, '/');
            $this->deletePath($destinationPath, $entryRel, $operation);
        }
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (! is_dir($destination)) {
            if (! @mkdir($destination, 0755, true) && ! is_dir($destination)) {
                throw new RuntimeException("Failed to create backup directory [{$destination}].");
            }
        }

        foreach ($this->listDirectory($source) as $entry) {
            $sourcePath = $this->join($source, $entry);
            $destinationPath = $this->join($destination, $entry);

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destinationPath);
            } elseif (is_file($sourcePath)) {
                if (! @copy($sourcePath, $destinationPath)) {
                    throw new RuntimeException("Failed to back up [{$sourcePath}].");
                }
            }
        }
    }

    private function copyFile(string $source, string $destination, string $relPath, string $operation): void
    {
        $dir = dirname($destination);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException("Failed to create parent dir for [{$relPath}] during {$operation}.");
        }

        if (! @copy($source, $destination)) {
            $err = error_get_last()['message'] ?? 'unknown';
            throw new RuntimeException("Failed to copy [{$relPath}] during {$operation}: {$err}");
        }
    }

    private function deletePath(string $path, string $relPath, string $operation): void
    {
        if (is_dir($path)) {
            foreach ($this->listDirectory($path) as $entry) {
                $this->deletePath($this->join($path, $entry), $relPath . '/' . $entry, $operation);
            }
            if (! @rmdir($path)) {
                throw new RuntimeException("Failed to remove directory [{$relPath}] during {$operation}.");
            }
            return;
        }

        if (file_exists($path) && ! @unlink($path)) {
            throw new RuntimeException("Failed to remove file [{$relPath}] during {$operation}.");
        }
    }

    /**
     * @return array<int, string>
     */
    private function listDirectory(string $path): array
    {
        $entries = @scandir($path);
        if ($entries === false) {
            return [];
        }

        return array_values(array_filter($entries, static fn (string $e): bool => $e !== '.' && $e !== '..'));
    }

    private function join(string $a, string $b): string
    {
        return rtrim($a, "/\\") . DIRECTORY_SEPARATOR . ltrim($b, "/\\");
    }
}
