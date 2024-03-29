<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Http\Controllers\TipController;
use Flute\Core\Http\Middlewares\IsInstalledMiddleware;
use Flute\Core\Router\RouteDispatcher;
use Flute\Core\Support\AbstractServiceProvider;

class TipsServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder) : void
    {}

    public function boot(\DI\Container $container) : void
    {
        $container->get(RouteDispatcher::class)->post('api/tip/complete', [TipController::class, 'complete'], [IsInstalledMiddleware::class]);
    }
}