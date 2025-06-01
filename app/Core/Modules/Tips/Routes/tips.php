<?php

use Flute\Core\Modules\Tips\Controllers\TipController;

router()->post('/api/tip/complete', [TipController::class, 'complete'])->middleware('can:admin.boss');
