<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Auth\AuthenticationService;
use Flute\Core\Services\AuthService;

use Flute\Core\Support\AbstractServiceProvider;

class AuthServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            AuthenticationService::class => \DI\create(),
            AuthService::class => \DI\autowire(),
            'auth' => \DI\get(AuthService::class),
        ]);
    }

    /**
     * Register auth service.
     */
    public function boot(\DI\Container $container): void
    {
        if (is_installed()) {
            $container->get(AuthService::class)->setRoutes();

            // temp
            app()->getLoader()->addPsr4('Hybridauth\\Provider\\', BASE_PATH . 'app/Core/Auth/Hybrid');
        }
    }
}