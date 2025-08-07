<?php

use Flute\Admin\Packages\Modules\Controllers\ModulesController;
use Flute\Admin\Packages\Modules\Screens\ModuleScreen;
use Flute\Core\Router\Router;

Router::screen('admin/modules', ModuleScreen::class);
router()->post('admin/modules/install', [ModulesController::class, 'installModule'])->middleware(['can:admin.modules'])->name('admin.modules.install');
