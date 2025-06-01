<?php

use Flute\Core\Modules\Translation\Controllers\TranslationController;

$router->post('api/translate', [TranslationController::class, 'translate'])->name('translation.translate')->middleware('throttle');
$router->post('admin/api/translate', [TranslationController::class, 'translate'])->name('translation.admin.translate');