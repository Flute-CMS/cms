<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Auth\AuthenticationService;
use Flute\Core\Auth\SocialService;

use Flute\Core\Support\AbstractServiceProvider;

class SocialServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder) : void
    {
        $containerBuilder->addDefinitions([
            SocialService::class => \DI\create(),
            'social' => \DI\get(SocialService::class),
        ]);
    }

    /**
     * Register social service.
     */
    public function boot(\DI\Container $container) : void
    {}
}