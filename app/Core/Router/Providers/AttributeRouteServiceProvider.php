<?php

namespace Flute\Core\Router\Providers;

use DI\Container;
use DI\ContainerBuilder;
use Flute\Core\Router\AttributeRouteLoader;
use Flute\Core\Router\Router;
use Flute\Core\Support\AbstractServiceProvider;

class AttributeRouteServiceProvider extends AbstractServiceProvider
{
    /**
     * Register services with the container builder
     *
     * @param ContainerBuilder $containerBuilder
     * @return void
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            AttributeRouteLoader::class => \DI\factory(function (Container $container) {
                $router = $container->get(Router::class);
                return new AttributeRouteLoader($router);
            }),
        ]);
    }

    /**
     * Boot services after all providers are registered
     *
     * @param Container $container
     * @return void
     */
    public function boot(Container $container): void
    {
    }
} 