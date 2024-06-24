<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Events\RoutingFinishedEvent;
use Flute\Core\Listeners\RedirectsListener;
use Flute\Core\Support\AbstractServiceProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Flute\Core\Support\FluteEventDispatcher;
use DI\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
            FluteEventDispatcher::class => \DI\autowire(),
            EventDispatcher::class => \DI\get(FluteEventDispatcher::class),
            EventDispatcherInterface::class => \DI\get(FluteEventDispatcher::class),
            "events" => \DI\get(FluteEventDispatcher::class)
        ]);
    }

    /**
     * Bootstrap the application services.
     * 
     * @return void
     */
    public function boot(\DI\Container $container): void
    {
        $container->get('events')->addListener(RoutingFinishedEvent::NAME, [RedirectsListener::class, 'onRoutingFinished']);
    }
}
