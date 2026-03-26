<?php

use Flute\Admin\Packages\Redirects\Screens\EditRedirectScreen;
use Flute\Admin\Packages\Redirects\Screens\RedirectsScreen;
use Flute\Core\Router\Router;

Router::screen('/admin/redirects', RedirectsScreen::class);
Router::screen('/admin/redirects/edit', EditRedirectScreen::class);
