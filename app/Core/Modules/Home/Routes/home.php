<?php

use Flute\Core\Modules\Home\Controllers\HomeController;
use Flute\Core\Modules\Installer\Middlewares\IsInstalledMiddleware;

$router->any('/', [HomeController::class, 'index'])->name('home')->middleware(IsInstalledMiddleware::class);