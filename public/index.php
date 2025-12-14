<?php

use Flute\Core\App;

/**
 * Get the application instance
 * 
 * @var App $app
 */
$app = require_once __DIR__ . '/../bootstrap/app.php';

/**
 * Run the application
 */
$app->run();