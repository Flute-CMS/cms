<?php

use Flute\Admin\Packages\Marketplace\Screens\MarketplaceScreen;
use Flute\Admin\Packages\Marketplace\Screens\MarketplaceProductScreen;
use Flute\Core\Router\Router;

Router::screen('/admin/marketplace', MarketplaceScreen::class);
Router::screen('/admin/marketplace/{slug}', MarketplaceProductScreen::class);
