<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Events\ResponseEvent;
use Flute\Core\Listeners\ToastResponseListener;
use Flute\Core\Services\FlashService;
use Flute\Core\Services\ToastService;
use Flute\Core\Support\AbstractServiceProvider;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class FlashServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the services provided by the service provider.
     */
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            ToastService::class => \DI\autowire(),
            FlashService::class => \DI\autowire(),
            FlashBagInterface::class => \DI\get(FlashService::class),
            "flash" => \DI\get(FlashService::class),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
        if (is_installed() && !is_cli()) {
            events()->addListener(ResponseEvent::NAME, [ToastResponseListener::class, 'onRouteResponse']);
        }
    }
}
