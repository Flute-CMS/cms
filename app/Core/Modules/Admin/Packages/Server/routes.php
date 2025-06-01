<?php

//

use Flute\Admin\Packages\Server\Screens\ServerEditScreen;
use Flute\Admin\Packages\Server\Screens\ServerListScreen;
use Flute\Core\Router\Router;

Router::screen('/admin/servers', ServerListScreen::class);
Router::screen('/admin/servers/add', ServerEditScreen::class);
Router::screen('/admin/servers/{id}/edit', ServerEditScreen::class);

