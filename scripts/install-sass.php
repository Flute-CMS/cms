<?php

/**
 * Auto-download dart-sass binary after composer install/update.
 * Silently skips if already installed or if download fails.
 */

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

require_once BASE_PATH . 'vendor/autoload.php';

use Flute\Core\Template\NativeSassCompiler;

$compiler = new NativeSassCompiler();

if ($compiler->isNativeAvailable()) {
    exit(0);
}

echo "Downloading dart-sass binary...\n";

if (NativeSassCompiler::downloadBinary()) {
    echo "dart-sass installed successfully.\n";
} else {
    echo "Could not install dart-sass (SCSS will use PHP fallback).\n";
}
