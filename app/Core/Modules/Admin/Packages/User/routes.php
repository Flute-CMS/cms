<?php

use Flute\Admin\Packages\User\Screens\UserEditScreen;
use Flute\Admin\Packages\User\Screens\UserListScreen;
use Flute\Core\Router\Router;

Router::screen('/admin/users', UserListScreen::class);
Router::screen('/admin/users/{id}/edit', UserEditScreen::class);