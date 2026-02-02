<?php

use Flute\Admin\Packages\Backup\Controllers\BackupController;
use Flute\Admin\Packages\Backup\Screens\BackupScreen;
use Flute\Core\Router\Router;

Router::screen('admin/backups', BackupScreen::class);
router()->get('admin/backups/download', [BackupController::class, 'download'])
    ->middleware('can:admin.system')
    ->name('admin.backups.download');
