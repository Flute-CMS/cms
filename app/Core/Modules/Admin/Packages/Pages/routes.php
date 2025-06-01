<?php

use Flute\Admin\Packages\Pages\Screens\PageListScreen;
use Flute\Admin\Packages\Pages\Screens\PageEditScreen;
use Flute\Core\Router\Router;

Router::screen('/admin/pages', PageListScreen::class);
Router::screen('/admin/pages/add', PageEditScreen::class);
Router::screen('/admin/pages/{id}/edit', PageEditScreen::class); 