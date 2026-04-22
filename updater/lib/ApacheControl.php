<?php

declare(strict_types=1);

/**
 * Controls Apache across both common XAMPP configurations:
 *
 *   1. Installed as a Windows service (via XAMPP control panel "Install" button).
 *      Uses `net stop`/`net start <service>`.
 *
 *   2. Run interactively via apache_start.bat from the XAMPP control panel.
 *      Falls back to taskkill httpd.exe + launch apache_start.bat detached.
 *
 * The caller configures both paths; we prefer the service, fall back to
 * process-level control if the service isn't registered or fails.
 */
final class ApacheControl
{
    public function __construct(
        private ?string $serviceName,
        private ?string $startBat,
        private StatusWriter $status,
    ) {}

    public function stop(): void
    {
        if ($this->serviceName !== null && $this->serviceIsRegistered($this->serviceName)) {
            $this->status->update('apache_stop', "Stopping Apache service [{$this->serviceName}]");
            [$exit, $output] = $this->runCommand(['net', 'stop', $this->serviceName]);

            if ($exit === 0 || str_contains($output, '2182') || stripos($output, 'not started') !== false) {
                $this->waitForHttpdToExit(20);
                return;
            }

            $this->status->update('apache_stop', "Service stop returned {$exit}; falling back to process kill");
        }

        $this->killHttpdProcesses();
        $this->waitForHttpdToExit(20);
    }

    public function start(): void
    {
        if ($this->serviceName !== null && $this->serviceIsRegistered($this->serviceName)) {
            $this->status->update('apache_start', "Starting Apache service [{$this->serviceName}]");
            [$exit, $output] = $this->runCommand(['net', 'start', $this->serviceName]);

            if ($exit === 0 || stripos($output, 'already been started') !== false) {
                return;
            }

            throw new RuntimeException("Failed to start Apache service: {$output}");
        }

        if ($this->startBat === null || ! is_file($this->startBat)) {
            throw new RuntimeException('No Apache start mechanism configured (neither service nor start bat).');
        }

        $this->status->update('apache_start', "Launching {$this->startBat} detached");
        // Launch detached so this PHP process can exit without killing Apache.
        pclose(popen('start "" /B "' . $this->startBat . '"', 'r'));
    }

    private function serviceIsRegistered(string $name): bool
    {
        [$exit, $output] = $this->runCommand(['sc', 'query', $name]);
        return $exit === 0 && stripos($output, 'SERVICE_NAME') !== false;
    }

    private function killHttpdProcesses(): void
    {
        $this->status->update('apache_stop', 'Terminating httpd.exe processes');
        $this->runCommand(['taskkill', '/F', '/IM', 'httpd.exe', '/T']);
    }

    private function waitForHttpdToExit(int $seconds): void
    {
        $deadline = time() + $seconds;
        while (time() < $deadline) {
            [$exit, $output] = $this->runCommand(['tasklist', '/FI', 'IMAGENAME eq httpd.exe']);
            if ($exit !== 0 || stripos($output, 'httpd.exe') === false) {
                return;
            }
            usleep(500_000);
        }

        throw new RuntimeException('httpd.exe processes did not exit within timeout.');
    }

    /**
     * @param array<int, string> $args
     * @return array{0: int, 1: string}
     */
    private function runCommand(array $args): array
    {
        $command = implode(' ', array_map(static fn (string $a): string => escapeshellarg($a), $args));
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);
        if (! is_resource($process)) {
            return [1, 'failed to launch process'];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exit = proc_close($process);
        return [$exit, trim($stdout . "\n" . $stderr)];
    }
}
