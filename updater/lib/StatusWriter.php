<?php

declare(strict_types=1);

/**
 * Writes progress to status.json so the Laravel app's status page can poll it
 * while Apache is stopped. The status file lives under storage/app/deployments/
 * which is not affected by the code swap.
 */
final class StatusWriter
{
    private string $path;
    private string $logPath;

    /** @var array<int, array{at: string, stage: string, message: string}> */
    private array $history = [];

    public function __construct(string $statusPath, string $logPath)
    {
        $this->path = $statusPath;
        $this->logPath = $logPath;

        $dir = dirname($this->path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $logDir = dirname($this->logPath);
        if (! is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
    }

    public function update(string $stage, string $message, string $state = 'running', array $extra = []): void
    {
        $entry = [
            'at' => date('c'),
            'stage' => $stage,
            'message' => $message,
        ];

        $this->history[] = $entry;
        $this->appendLog("[{$stage}] {$message}");

        $payload = array_merge([
            'state' => $state,
            'stage' => $stage,
            'message' => $message,
            'updated_at' => $entry['at'],
            'history' => $this->history,
        ], $extra);

        $tmp = $this->path . '.tmp';
        file_put_contents($tmp, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        @rename($tmp, $this->path);
    }

    public function finalize(string $state, string $message, array $extra = []): void
    {
        $this->update('finalize', $message, $state, $extra);
    }

    private function appendLog(string $line): void
    {
        $stamp = date('Y-m-d H:i:s');
        @file_put_contents($this->logPath, "[{$stamp}] {$line}\n", FILE_APPEND | LOCK_EX);
    }
}
