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
     * Auto-clears stale flag if:
     * - no active composer lock is held AND
     * - vendor/autoload.php exists (basic sanity check)
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

        $startedAt = null;
        if (!empty($payload['started_at']) && is_string($payload['started_at'])) {
            $ts = strtotime($payload['started_at']);
            if ($ts !== false) {
                $startedAt = $ts;
            }
        }

        $staleAfterSeconds = 60 * 30;
        $isStale = $startedAt !== null && (time() - $startedAt) >= $staleAfterSeconds;

        if ($startedAt === null) {
            $mtime = @filemtime(is_file($storageFlag) ? $storageFlag : $publicFlag);
            if ($mtime !== false) {
                $isStale = (time() - (int)$mtime) >= $staleAfterSeconds;
            }
        }

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
        header('Retry-After: 60');

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
     *
     * @param string $basePath Project base path with trailing slash.
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
