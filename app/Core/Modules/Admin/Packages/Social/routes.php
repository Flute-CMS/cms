<?php

use Flute\Admin\Packages\Social\Screens\EditSocialScreen;
use Flute\Admin\Packages\Social\Screens\SocialScreen;
use Flute\Core\Router\Router;

Router::screen('/admin/socials', SocialScreen::class);
Router::screen('/admin/socials/{id<\d+>}/edit', EditSocialScreen::class);
Router::screen('/admin/socials/add', EditSocialScreen::class);
