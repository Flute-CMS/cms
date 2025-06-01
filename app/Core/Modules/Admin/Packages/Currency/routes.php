<?php

use Flute\Core\Router\Router;
use Flute\Admin\Packages\Currency\Screens\CurrencyListScreen;

Router::screen('/admin/currency', CurrencyListScreen::class);