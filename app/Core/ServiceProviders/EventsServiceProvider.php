<?php

namespace Flute\Core\ServiceProviders;

use DI\ContainerBuilder;
use Flute\Core\Events\ResponseEvent;
use Flute\Core\Events\RoutingFinishedEvent;
use Flute\Core\Listeners\HeadersListener;
use Flute\Core\Listeners\RedirectsListener;
use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Support\FluteEventDispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EventsServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the services provided by the service provider.
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            FluteEventDispatcher::class => \DI\autowire(),
            EventDispatcher::class => \DI\get(FluteEventDispatcher::class),
            EventDispatcherInterface::class => \DI\get(FluteEventDispatcher::class),
            \Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class => \DI\get(FluteEventDispatcher::class),
            "events" => \DI\get(FluteEventDispatcher::class),
        ]);
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(\DI\Container $container): void
    {
        if (!is_cli()) {
            $container->get('events')->addListener(RoutingFinishedEvent::NAME, [RedirectsListener::class, 'onRoutingFinished']);
            $container->get('events')->addListener(ResponseEvent::NAME, [HeadersListener::class, 'onRouteResponse']);
            $container->get('events')->addListener(ResponseEvent::NAME, [\Flute\Core\Listeners\RequestTimingListener::class, 'onRouteResponse']);
        }
    }
}
