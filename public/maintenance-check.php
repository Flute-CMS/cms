<?php

declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$basePath = dirname(__DIR__) . DIRECTORY_SEPARATOR;

$storageFlag = $basePath . 'storage/app/.maintenance-composer';
$publicFlag = __DIR__ . DIRECTORY_SEPARATOR . '.maintenance-composer';
$vendorAutoload = $basePath . 'vendor/autoload.php';
$lockPath = $basePath . 'storage/composer/lock';

function flute_is_locked(string $lockPath): bool
{
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

function flute_payload(string $storageFlag): array
{
    if (!is_file($storageFlag)) {
        return [];
    }

    $raw = @file_get_contents($storageFlag);
    if (!is_string($raw) || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : [];
}

function flute_age(array $payload, string $storageFlag, string $publicFlag): int
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

function flute_process_alive(int $pid): bool
{
    if (is_dir('/proc') && is_readable('/proc/1')) {
        return is_dir("/proc/{$pid}");
    }

    if (function_exists('posix_kill')) {
        $alive = @posix_kill($pid, 0);
        if (!$alive && posix_get_last_error() === 1) {
            return true;
        }

        return $alive;
    }

    return true;
}

function flute_rrmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $items = @scandir($dir);
    if (!is_array($items)) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            flute_rrmdir($path);

            continue;
        }
        @unlink($path);
    }

    @rmdir($dir);
}

function flute_try_restore_vendor(string $basePath, array $payload): bool
{
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
        flute_rrmdir($failed);
    }

    return @rename($backupPath, $vendorDir);
}

// --- Main logic ---

$maintenance = is_file($storageFlag) || is_file($publicFlag);
if (!$maintenance) {
    http_response_code(204);
    exit;
}

// If vendor/autoload.php exists and composer is not running, the update
// finished but the flag wasn't cleaned up. Clear immediately.
if (is_file($vendorAutoload) && !flute_is_locked($lockPath)) {
    @unlink($storageFlag);
    @unlink($publicFlag);
    http_response_code(204);
    exit;
}

$payload = flute_payload($storageFlag);
$age = flute_age($payload, $storageFlag, $publicFlag);
$hardTimeout = 300; // 5 minutes

$isStale = $age >= $hardTimeout;

// PID liveness check (after 15s grace period)
if (!$isStale && $age >= 15 && !empty($payload['pid'])) {
    $pid = (int) $payload['pid'];
    if ($pid > 0 && !flute_process_alive($pid)) {
        $isStale = true;
    }
}

if ($isStale && !flute_is_locked($lockPath)) {
    $restored = flute_try_restore_vendor($basePath, $payload);

    if ($restored && is_file($vendorAutoload)) {
        @unlink($storageFlag);
        @unlink($publicFlag);
        http_response_code(204);
        exit;
    }

    // Even without vendor restore, clear stale flags if autoload exists
    if (is_file($vendorAutoload)) {
        @unlink($storageFlag);
        @unlink($publicFlag);
        http_response_code(204);
        exit;
    }
}

http_response_code(503);
exit;
