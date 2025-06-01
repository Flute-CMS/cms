<?php

namespace Flute\Core\Modules\Tips\Providers;

use Flute\Core\Support\AbstractServiceProvider;

class TipsServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
    }

    public function boot(\DI\Container $container): void
    {
        is_installed() &&
            $this->loadRoutesFrom(cms_path('Tips/Routes/tips.php'));
    }
}