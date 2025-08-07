<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Services\EncryptService;
use Flute\Core\Support\AbstractServiceProvider;

class EncryptServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the services provided by the service provider.
     */
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            EncryptService::class => \DI\factory(function (\DI\Container $container) {
                return new EncryptService(base64_decode($container->get('app.key')));
            }),
            "encrypt" => \DI\get(EncryptService::class),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
    }
}
