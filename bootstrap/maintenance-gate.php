<?php

declare(strict_types=1);

if (!function_exists('flute_maintenance_gate')) {
    function flute_maintenance_gate(string $basePath): bool
    {
        $basePath = rtrim($basePath, "\\/") . DIRECTORY_SEPARATOR;

        $storageFlag = $basePath . 'storage/app/.maintenance-composer';
        $publicFlag = $basePath . 'public/.maintenance-composer';

        if (!is_file($storageFlag) && !is_file($publicFlag)) {
            return false;
        }

        $payload = flute_parse_maintenance_payload($storageFlag, $publicFlag);
        $age = flute_maintenance_age($payload, $storageFlag, $publicFlag);

        if ($age > 300) {
            flute_clear_maintenance_flags($storageFlag, $publicFlag);

            return false;
        }

        if (is_file($basePath . 'vendor/autoload.php')) {
            $composerRunning = flute_is_composer_locked($basePath);
            $processAlive = !empty($payload['pid']) && flute_process_alive((int) $payload['pid']);

            if (!$composerRunning && !$processAlive) {
                flute_clear_maintenance_flags($storageFlag, $publicFlag);
            }

            return false;
        }

        $isStale = flute_maintenance_is_stale($payload, $age, $basePath);

        if ($isStale && !flute_is_composer_locked($basePath)) {
            flute_try_restore_vendor($basePath, $payload);

            if (is_file($basePath . 'vendor/autoload.php')) {
                flute_clear_maintenance_flags($storageFlag, $publicFlag);

                return false;
            }
        }

        $title = $payload['title'] ?? 'Maintenance';
        $message = $payload['message'] ?? 'Update in progress, please try again shortly.';

        if (!is_string($title) || $title === '') {
            $title = 'Maintenance';
        }
        if (!is_string($message) || $message === '') {
            $message = 'Update in progress, please try again shortly.';
        }

        http_response_code(503);
        header('Content-Type: text/html; charset=UTF-8');
        header('Retry-After: 10');

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
                    * { box-sizing: border-box; }
                    body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0c0c0f; color: #f4f4f5; }
                    .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
                    .card { max-width: 520px; width: 100%; background: #141419; border: 1px solid #2a2a35; border-radius: 12px; padding: 24px; }
                    h1 { font-size: 16px; margin: 0 0 8px; }
                    p { margin: 0; color: #a1a1aa; font-size: 13px; line-height: 1.5; }
                    .progress { margin-top: 16px; height: 3px; background: #2a2a35; border-radius: 2px; overflow: hidden; }
                    .progress-bar { height: 100%; width: 30%; background: #6366f1; border-radius: 2px; animation: progress 1.5s ease-in-out infinite; }
                    @keyframes progress { 0% { transform: translateX(-100%); width: 30%; } 50% { width: 60%; } 100% { transform: translateX(400%); width: 30%; } }
                    .status { margin-top: 12px; display: flex; align-items: center; justify-content: space-between; }
                    .dot { width: 6px; height: 6px; border-radius: 50%; background: #22c55e; display: inline-block; margin-right: 6px; animation: pulse 2s ease-in-out infinite; }
                    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .4; } }
                    .status-text { font-size: 11px; color: #71717a; display: flex; align-items: center; }
                    .timer { font-size: 11px; color: #71717a; font-variant-numeric: tabular-nums; }
                </style>
            </head>
            <body>
                <div class="wrap">
                    <div class="card">
                        <h1>{$safeTitle}</h1>
                        <p>{$safeMessage}</p>
                        <div class="progress"><div class="progress-bar"></div></div>
                        <div class="status">
                            <span class="status-text"><span class="dot"></span>Checking...</span>
                            <span class="timer" id="timer"></span>
                        </div>
                    </div>
                </div>
                <script>
                (function(){
                    var start = Date.now();
                    var timer = document.getElementById('timer');
                    var statusText = document.querySelector('.status-text');
                    function pad(n){ return n < 10 ? '0' + n : n; }
                    setInterval(function(){
                        var s = Math.floor((Date.now() - start) / 1000);
                        timer.textContent = pad(Math.floor(s/60)) + ':' + pad(s%60);
                    }, 1000);

                    function check(){
                        var x = new XMLHttpRequest();
                        x.open('GET', '/maintenance-check.php?_=' + Date.now(), true);
                        x.timeout = 5000;
                        x.onload = function(){
                            if(x.status === 204){
                                statusText.innerHTML = '<span class="dot" style="background:#22c55e"></span>Ready!';
                                location.reload();
                            }
                        };
                        x.onerror = x.ontimeout = function(){};
                        x.send();
                    }
                    setInterval(check, 3000);
                    setTimeout(check, 1000);
                })();
                </script>
            </body>
            </html>
            HTML;

        return true;
    }

    function flute_parse_maintenance_payload(string $storageFlag, string $publicFlag): array
    {
        foreach ([$storageFlag, $publicFlag] as $flag) {
            if (!is_file($flag)) {
                continue;
            }

            $raw = @file_get_contents($flag);
            if (!is_string($raw) || $raw === '') {
                continue;
            }

            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    function flute_clear_maintenance_flags(string $storageFlag, string $publicFlag): void
    {
        @unlink($storageFlag);
        @unlink($publicFlag);
    }

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

    function flute_maintenance_is_stale(array $payload, int $ageSeconds, string $basePath): bool
    {
        if ($ageSeconds >= 300) {
            return true;
        }

        if ($ageSeconds < 15) {
            return false;
        }

        if (!empty($payload['pid'])) {
            $pid = (int) $payload['pid'];
            if ($pid > 0 && !flute_process_alive($pid)) {
                return true;
            }
        }

        return false;
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
