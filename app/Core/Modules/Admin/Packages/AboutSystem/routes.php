<?php

use Flute\Admin\Packages\AboutSystem\Controllers\SystemReportController;
use Flute\Admin\Packages\AboutSystem\Screens\AboutSystemScreen;
use Flute\Core\Router\Router;

Router::screen('/admin/about-system', AboutSystemScreen::class);

router()->get('/admin/about-system/download-report', [SystemReportController::class, 'download'])
    ->middleware(['can:admin.boss', 'csrf']);
