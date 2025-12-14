<?php

use Flute\Admin\Packages\MainSettings\Screens\MainSettingsPackageScreen;
use Flute\Core\Router\Router;

Router::screen('/admin/main-settings', MainSettingsPackageScreen::class);
