<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Payments\GatewayInitializer;
use Flute\Core\Payments\PaymentRoutes;
use Flute\Core\Support\AbstractServiceProvider;

class PaymentServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder) : void
    {
        $containerBuilder->addDefinitions([
            GatewayInitializer::class => \DI\autowire(),
            PaymentRoutes::class => \DI\autowire(),
        ]);
    }

    public function boot( \DI\Container $container ): void
    {
        if (!is_installed())
            return;
        
        $container->get(GatewayInitializer::class);
        $container->get(PaymentRoutes::class)->init();
    }
}
