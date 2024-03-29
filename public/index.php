<?php

use Flute\Core\App;

define('FLUTE_START', microtime(true));
define('BASE_PATH', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);

/**
 * Get the application instance
 * 
 * @var App $app
 */
$app = require_once BASE_PATH . 'bootstrap/app.php';

/**
 * Run the application
 */
$app->run();