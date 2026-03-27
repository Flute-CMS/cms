<?php

declare(strict_types=1);

/**
 * Early maintenance gate for Composer operations.
 *
 * This file must not rely on vendor autoload or application container.
 */

if (!function_exists('flute_maintenance_gate')) {
    /**
     * If maintenance flag is set, render a 503 page and stop execution.
     *
     * Auto-clears stale flag when the updater process is no longer alive.
     *
     * @param string $basePath Project base path with trailing slash.
     */
    function flute_maintenance_gate(string $basePath): bool
    {
        $basePath = rtrim($basePath, "\\/") . DIRECTORY_SEPARATOR;

        $storageFlag = $basePath . 'storage/app/.maintenance-composer';
        $publicFlag = $basePath . 'public/.maintenance-composer';

        if (!is_file($storageFlag) && !is_file($publicFlag)) {
            return false;
        }

        $payload = [];
        if (is_file($storageFlag)) {
            $raw = @file_get_contents($storageFlag);
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $payload = $decoded;
                }
            }
        }

        // Determine how long maintenance has been active
        $age = flute_maintenance_age($payload, $storageFlag, $publicFlag);

        // Check if the updater process is still alive
        $isStale = flute_maintenance_is_stale($payload, $age, $basePath);

        if ($isStale && !flute_is_composer_locked($basePath)) {
            if (!is_file($basePath . 'vendor/autoload.php')) {
                flute_try_restore_vendor($basePath, $payload);
            }

            if (is_file($basePath . 'vendor/autoload.php')) {
                @unlink($storageFlag);
                @unlink($publicFlag);

                return false;
            }
        }

        $title = 'Maintenance';
        $message = 'Updating, please try again in a minute.';

        if (!empty($payload['title']) && is_string($payload['title'])) {
            $title = $payload['title'];
        }
        if (!empty($payload['message']) && is_string($payload['message'])) {
            $message = $payload['message'];
        }

        http_response_code(503);
        header('Content-Type: text/html; charset=UTF-8');
        header('Retry-After: 30');

        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        echo <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>{$safeTitle}</title>
                <style>
                    :root { color-scheme: dark; }
                    body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0c0c0f; color: #f4f4f5; }
                    .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
                    .card { max-width: 520px; width: 100%; background: #141419; border: 1px solid #2a2a35; border-radius: 12px; padding: 20px; }
                    h1 { font-size: 16px; margin: 0 0 8px; }
                    p { margin: 0; color: #a1a1aa; font-size: 13px; line-height: 1.5; }
                </style>
            </head>
            <body>
                <div class="wrap">
                    <div class="card">
                        <h1>{$safeTitle}</h1>
                        <p>{$safeMessage}</p>
                    </div>
                </div>
            </body>
            </html>
            HTML;

        return true;
    }

    /**
     * Calculate how many seconds maintenance has been active.
     */
    function flute_maintenance_age(array $payload, string $storageFlag, string $publicFlag): int
    {
        if (!empty($payload['started_at']) && is_string($payload['started_at'])) {
            $ts = strtotime($payload['started_at']);
            if ($ts !== false) {
                return max(0, time() - $ts);
            }
        }

        $flagFile = is_file($storageFlag) ? $storageFlag : $publicFlag;
        $mtime = @filemtime($flagFile);
        if ($mtime !== false) {
            return max(0, time() - (int) $mtime);
        }

        return 0;
    }

    /**
     * Determine if the maintenance flag is stale (updater process is gone).
     *
     * Strategy (in order):
     *  1. If a PID is recorded — check if that process is still alive.
     *     Works on Linux (/proc), Unix (posix_kill), safe no-op elsewhere.
     *     If the process is dead → stale immediately.
     *  2. Hard timeout — if maintenance has been active for 10+ minutes,
     *     assume the process crashed without cleanup.
     */
    function flute_maintenance_is_stale(array $payload, int $ageSeconds, string $basePath): bool
    {
        // Hard ceiling — no update should take this long
        $hardTimeoutSeconds = 600; // 10 minutes

        if ($ageSeconds >= $hardTimeoutSeconds) {
            return true;
        }

        // Give the process at least 30 seconds before checking PID
        // (avoids race condition on startup)
        if ($ageSeconds < 30) {
            return false;
        }

        // PID-based liveness check (cross-platform, best-effort)
        if (!empty($payload['pid'])) {
            $pid = (int) $payload['pid'];
            if ($pid > 0 && !flute_process_alive($pid)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a process is still running. Cross-platform, no shell_exec.
     *
     * Returns true if alive OR if we cannot determine (safe default).
     */
    function flute_process_alive(int $pid): bool
    {
        // Linux: /proc filesystem (fast stat, no syscall overhead)
        if (is_dir('/proc') && is_readable('/proc/1')) {
            return is_dir("/proc/{$pid}");
        }

        // Unix with posix extension (macOS, BSD, Linux without /proc)
        if (function_exists('posix_kill')) {
            // Signal 0 tests existence without sending a real signal
            $alive = @posix_kill($pid, 0);

            // EPERM means process exists but we lack permission — still alive
            if (!$alive && posix_get_last_error() === 1) { // EPERM
                return true;
            }

            return $alive;
        }

        // Cannot determine — assume alive (safe default, rely on hard timeout)
        return true;
    }

    function flute_try_restore_vendor(string $basePath, array $payload): bool
    {
        $basePath = rtrim($basePath, "\\/") . DIRECTORY_SEPARATOR;
        $vendorDir = $basePath . 'vendor';

        if (is_file($basePath . 'vendor/autoload.php')) {
            return true;
        }

        $backup = $payload['vendor_backup'] ?? null;
        if (!is_string($backup) || $backup === '') {
            return false;
        }

        $backupPath = $basePath . $backup;
        if (!is_dir($backupPath)) {
            return false;
        }

        if (is_dir($vendorDir)) {
            $failed = $basePath . 'vendor.__failed__' . date('Ymd-His');
            @rename($vendorDir, $failed);
        }

        return @rename($backupPath, $vendorDir);
    }

    /**
     * Detects an active composer lock without blocking.
     */
    function flute_is_composer_locked(string $basePath): bool
    {
        $basePath = rtrim($basePath, "\\/") . DIRECTORY_SEPARATOR;

        $lockPath = $basePath . 'storage/composer/lock';
        $handle = @fopen($lockPath, 'c+');
        if ($handle === false) {
            return false;
        }

        $locked = !@flock($handle, LOCK_EX | LOCK_NB);
        if (!$locked) {
            @flock($handle, LOCK_UN);
        }

        @fclose($handle);

        return $locked;
    }
}
