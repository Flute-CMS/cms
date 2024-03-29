<?php

namespace Flute\Core\ServiceProviders;


use Flute\Core\Support\AbstractServiceProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use DI\ContainerBuilder;

class EventsServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the services provided by the service provider.
     * 
     * @param ContainerBuilder $containerBuilder
     * 
     * @return void
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            EventDispatcher::class => \DI\create(),
            EventDispatcherInterface::class => \DI\get(EventDispatcher::class),
            "events" => \DI\get(EventDispatcher::class)
        ]);
    }

    /**
     * Bootstrap the application services.
     * 
     * @return void
     */
    public function boot( \DI\Container $container ): void
    {}
}
