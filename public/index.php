<?php

use Flute\Core\App;

$basePath = dirname(__DIR__) . DIRECTORY_SEPARATOR;
require_once $basePath . 'bootstrap/maintenance-gate.php';
if (flute_maintenance_gate($basePath)) {
    exit;
}

/**
 * Get the application instance
 *
 * @var App $app
 */
$app = require_once __DIR__ . '/../bootstrap/app.php';

/*
 * Run the application
 */
$app->run();
