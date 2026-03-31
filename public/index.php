<?php

use Flute\Core\App;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;

$basePath = dirname(__DIR__) . DIRECTORY_SEPARATOR;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', $basePath);
}

require_once $basePath . 'bootstrap/fatal-error-handler.php';
flute_register_fatal_handler();

require_once $basePath . 'bootstrap/maintenance-gate.php';
if (flute_maintenance_gate($basePath)) {
    exit;
}

try {
    /** @var App $app */
    $app = require_once $basePath . 'bootstrap/app.php';
    $app->run();
} catch (\Throwable $e) {
    if ($e instanceof SuspiciousOperationException) {
        http_response_code(400);
        exit('Bad Request');
    }

    // Crash report — try full service, fall back to primitive
    if (class_exists(\Flute\Core\Services\CrashReportService::class, false)) {
        \Flute\Core\Services\CrashReportService::capture($e, ['source' => 'index']);
    } else {
        $payload = flute_build_crash_payload(
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            (int) $e->getCode(),
            $e->getTrace(),
        );
        flute_crash_report($payload);
    }

    $isDebug = defined('FLUTE_DEBUG') && FLUTE_DEBUG;

    if (function_exists('logs')) {
        try {
            logs()->critical($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        } catch (\Throwable) {
        }
    }

    if (class_exists(\Tracy\Debugger::class, false) && \Tracy\Debugger::isEnabled()) {
        throw $e;
    }

    flute_render_emergency_page(
        500,
        $isDebug ? $e->getMessage() : 'Internal Server Error',
        $isDebug ? $e->getFile() : null,
        $isDebug ? $e->getLine() : null,
    );
}
