<?php

declare(strict_types=1);

if (!function_exists('flute_register_fatal_handler')) {
    function flute_early_detect_debug(string $basePath): bool
    {
        $configFile = rtrim($basePath, "\\/") . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';

        if (!is_file($configFile)) {
            return false;
        }

        $config = @include $configFile;

        if (!is_array($config)) {
            return false;
        }

        if (!empty($config['development_mode'])) {
            return true;
        }

        if (empty($config['debug'])) {
            return false;
        }

        $debugIps = $config['debug_ips'] ?? [];

        if (empty($debugIps) || !is_array($debugIps)) {
            return true;
        }

        $clientIp = $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '';

        if (str_contains($clientIp, ',')) {
            $clientIp = trim(explode(',', $clientIp)[0]);
        }

        return in_array($clientIp, $debugIps, true);
    }

    function flute_crash_report(array $payload): void
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $configFile = rtrim($basePath, "\\/") . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';

        if (!is_file($configFile)) {
            return;
        }

        $config = @include $configFile;

        if (!is_array($config) || empty($config['share'])) {
            return;
        }

        $rateLimitDir = rtrim($basePath, "\\/") . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'crash-reports';

        if (!is_dir($rateLimitDir)) {
            @mkdir($rateLimitDir, 0755, true);
        }

        if (is_dir($rateLimitDir)) {
            $fp = $payload['fingerprint'] ?? md5(($payload['file'] ?? '') . ':' . ($payload['line'] ?? 0));
            $fpFile = $rateLimitDir . DIRECTORY_SEPARATOR . 'crash_' . $fp;

            if (is_file($fpFile) && (time() - (int) filemtime($fpFile)) < 300) {
                return;
            }

            $hourlyFile = $rateLimitDir . DIRECTORY_SEPARATOR . 'crash_hourly_count';
            $data = null;

            if (is_file($hourlyFile)) {
                $data = @json_decode((string) @file_get_contents($hourlyFile), true);

                if (is_array($data) && ($data['hour'] ?? '') === date('YmdH') && ($data['count'] ?? 0) >= 10) {
                    return;
                }
            }

            @touch($fpFile);
            $hData = (is_array($data) && ($data['hour'] ?? '') === date('YmdH'))
                ? ['hour' => date('YmdH'), 'count' => ($data['count'] ?? 0) + 1]
                : ['hour' => date('YmdH'), 'count' => 1];
            @file_put_contents($hourlyFile, json_encode($hData));
        }

        if (class_exists(\Flute\Core\Services\CrashReportService::class, false)) {
            return;
        }

        $url = rtrim($config['flute_market_url'] ?? 'https://flute-cms.com', '/') . '/api/crash-reports';
        $body = json_encode(['reports' => [$payload]]);

        if (!function_exists('curl_init')) {
            return;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'User-Agent: Flute-CMS/unknown'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);
        @curl_exec($ch);
        @curl_close($ch);
    }

    function flute_build_crash_payload(string $class, string $message, string $file, int $line, int $code = 0, array $trace = []): array
    {
        $basePath = defined('BASE_PATH') ? rtrim(BASE_PATH, "\\/") . '/' : '';
        $sensitivePattern = '/(password|secret|token|key|auth|cookie|session|credential|dsn|api_key|authorization)[\s]*[=:]\s*[^\s,;]+/i';
        $message = (string) preg_replace($sensitivePattern, '$1=[REDACTED]', $message);

        $relFile = $file;
        if ($basePath !== '') {
            $normBase = str_replace('\\', '/', $basePath);
            $normFile = str_replace('\\', '/', $file);
            if (str_starts_with($normFile, $normBase)) {
                $relFile = substr($normFile, strlen($normBase));
            }
        }

        $cleanTrace = [];
        foreach (array_slice($trace, 0, 15) as $frame) {
            $tf = str_replace('\\', '/', $frame['file'] ?? '');
            if ($basePath !== '' && $tf !== '') {
                $bp = str_replace('\\', '/', $basePath);
                if (str_starts_with($tf, $bp)) {
                    $tf = substr($tf, strlen($bp));
                }
            }
            $cleanTrace[] = [
                'file' => $tf,
                'line' => $frame['line'] ?? 0,
                'class' => $frame['class'] ?? null,
                'function' => $frame['function'] ?? null,
            ];
        }

        return [
            'exception_class' => $class,
            'message' => $message,
            'code' => $code,
            'file' => $relFile,
            'line' => $line,
            'trace' => $cleanTrace,
            'php_version' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
            'cms_version' => defined('Flute\Core\App::VERSION') ? \Flute\Core\App::VERSION : 'unknown',
            'modules' => [],
            'themes' => [],
            'url_path' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'fingerprint' => md5($class . ':' . $file . ':' . $line),
            'timestamp' => date('c'),
            'source' => 'fatal',
        ];
    }

    function flute_register_fatal_handler(): void
    {
        $GLOBALS['__flute_reserved_memory'] = str_repeat('x', 32768);

        if (!defined('FLUTE_DEBUG') && defined('BASE_PATH')) {
            define('FLUTE_DEBUG', flute_early_detect_debug(BASE_PATH));
        }

        register_shutdown_function(static function (): void {
            $GLOBALS['__flute_reserved_memory'] = null;

            $error = error_get_last();

            if ($error === null) {
                return;
            }

            if (!($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
                return;
            }

            if (preg_match('/^Allowed memory size of (\d+) bytes exhausted/', $error['message'], $m)) {
                @ini_set('memory_limit', (string) ((int) $m[1] + 5 * 1024 * 1024));
            }

            if (class_exists(\Flute\Core\Services\CrashReportService::class, false)) {
                \Flute\Core\Services\CrashReportService::captureFatal($error);
            } else {
                flute_crash_report(flute_build_crash_payload(
                    'FatalError',
                    $error['message'],
                    $error['file'] ?? '',
                    $error['line'] ?? 0,
                    $error['type'],
                ));
            }

            if (headers_sent()) {
                return;
            }

            if (php_sapi_name() === 'cli') {
                fwrite(STDERR, "\n[FATAL] {$error['message']} in {$error['file']}:{$error['line']}\n");

                return;
            }

            flute_render_emergency_page(
                500,
                $error['message'],
                $error['file'],
                $error['line'],
            );
        });

        $previousExceptionHandler = set_exception_handler(null);
        restore_exception_handler();

        set_exception_handler(static function (\Throwable $e) use ($previousExceptionHandler): void {
            if (class_exists(\Flute\Core\Services\CrashReportService::class, false)) {
                \Flute\Core\Services\CrashReportService::capture($e, ['source' => 'uncaught']);
            } else {
                flute_crash_report(flute_build_crash_payload(
                    get_class($e),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    (int) $e->getCode(),
                    $e->getTrace(),
                ));
            }

            if ($previousExceptionHandler !== null) {
                $previousExceptionHandler($e);

                return;
            }

            if (php_sapi_name() === 'cli') {
                fwrite(STDERR, "\n[UNCAUGHT] " . get_class($e) . ": {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}\n");
                exit(1);
            }

            if (headers_sent()) {
                return;
            }

            $isDebug = defined('FLUTE_DEBUG') && FLUTE_DEBUG;

            if (!$isDebug && class_exists(\Tracy\Debugger::class, false) && \Tracy\Debugger::isEnabled()) {
                return;
            }

            flute_render_emergency_page(
                500,
                $isDebug ? $e->getMessage() : 'Internal Server Error',
                $isDebug ? $e->getFile() : null,
                $isDebug ? $e->getLine() : null,
            );

            exit(1);
        });
    }

    function flute_render_emergency_page(int $code, string $message, ?string $file = null, ?int $line = null): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($code);
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store');

        $isDebug = defined('FLUTE_DEBUG') && FLUTE_DEBUG;
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $detail = '';

        if ($isDebug && $file !== null) {
            $safeFile = htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
            $detail = "<p class=\"detail\">{$safeFile}:{$line}</p>";
        }

        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error {$code}</title>
    <style>
        :root{color-scheme:dark}
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#0c0c0f;color:#f4f4f5;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
        .card{max-width:540px;width:100%;background:#141419;border:1px solid #2a2a35;border-radius:12px;padding:32px}
        .code{font-size:64px;font-weight:700;color:#6366f1;line-height:1;margin-bottom:8px}
        h1{font-size:18px;font-weight:600;margin-bottom:12px}
        .msg{font-size:13px;color:#a1a1aa;line-height:1.6;word-break:break-word}
        .detail{font-size:11px;color:#71717a;margin-top:12px;font-family:monospace;word-break:break-all}
        .actions{margin-top:20px;display:flex;gap:8px}
        a{display:inline-flex;align-items:center;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none;transition:background .15s}
        .primary{background:#6366f1;color:#fff}
        .primary:hover{background:#4f46e5}
        .secondary{background:#1f1f28;color:#a1a1aa;border:1px solid #2a2a35}
        .secondary:hover{background:#2a2a35}
    </style>
</head>
<body>
    <div class="card">
        <div class="code">{$code}</div>
        <h1>Something went wrong</h1>
        <p class="msg">{$safeMessage}</p>
        {$detail}
        <div class="actions">
            <a href="/" class="primary">Go Home</a>
            <a href="javascript:location.reload()" class="secondary">Retry</a>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
