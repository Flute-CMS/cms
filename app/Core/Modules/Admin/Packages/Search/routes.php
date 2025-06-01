<?php

use Flute\Admin\Packages\Search\Controllers\AdminSelectController;
use Flute\Admin\Packages\Search\Controllers\AdminSearchController;

router()->get('/admin/search', [AdminSearchController::class, 'search']);
router()->get('/admin/search/commands', [AdminSearchController::class, 'slashCommands']);
router()->get('/admin/select/search', [AdminSelectController::class, 'search']);
