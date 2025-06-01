<?php

namespace Flute\Core\Modules\Icons\Providers;

use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Modules\Icons\Controllers\IconController;
use Flute\Core\Router\Router;

class IconServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void {}

    public function boot(\DI\Container $container): void
    {
        if (is_installed() && is_admin_path()) {
            $router = $container->get(Router::class);
            $router->registerAttributeRoutesFromClass(IconController::class);
        }
    }
}
