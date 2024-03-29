<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Http\Controllers\HomeController;

use Flute\Core\Http\Middlewares\IsInstalledMiddleware;
use Flute\Core\Router\RouteDispatcher;
use Flute\Core\Support\AbstractServiceProvider;

class HomeServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder) : void {}

    public function boot(\DI\Container $container) : void
    {
        is_installed() && $container->get(RouteDispatcher::class)
            ->any('/', [HomeController::class, 'index'], [IsInstalledMiddleware::class]);
    }
}