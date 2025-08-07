<?php

use Flute\Admin\Packages\Search\Controllers\AdminSearchController;
use Flute\Admin\Packages\Search\Controllers\AdminSelectController;

router()->get('/admin/search', [AdminSearchController::class, 'search']);
router()->get('/admin/search/commands', [AdminSearchController::class, 'slashCommands']);
router()->get('/admin/select/search', [AdminSelectController::class, 'search']);
