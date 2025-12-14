<?php

use Flute\Admin\Packages\ApiKey\Screens\ApiKeyScreen;
use Flute\Core\Router\Router;

Router::screen('/admin/api-keys', ApiKeyScreen::class);
