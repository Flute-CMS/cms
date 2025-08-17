<?php

use Flute\Admin\Packages\Search\Controllers\AdminSearchController;
use Flute\Admin\Packages\Search\Controllers\AdminSelectController;

router()->get('/admin/search', [AdminSearchController::class, 'search'])->middleware('can:admin');
router()->get('/admin/search/commands', [AdminSearchController::class, 'slashCommands'])->middleware('can:admin');
router()->get('/admin/select/search', [AdminSelectController::class, 'search'])->middleware('can:admin');
