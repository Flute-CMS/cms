<?php

use Flute\Admin\Packages\Currency\Screens\CurrencyListScreen;
use Flute\Core\Router\Router;

Router::screen('/admin/currency', CurrencyListScreen::class);
