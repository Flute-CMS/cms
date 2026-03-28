<?php

use Flute\Core\App;

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
