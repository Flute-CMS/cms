<?php

namespace Flute\Core\Modules\Home\Providers;

use Flute\Core\Support\AbstractServiceProvider;

class HomeServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
    }

    public function boot(\DI\Container $container): void
    {
        is_installed() && $this->loadRoutesFrom(cms_path('Home/Routes/home.php'));
    }
}
