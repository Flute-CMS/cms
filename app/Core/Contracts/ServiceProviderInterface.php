<?php

namespace Flute\Core\Contracts;

use Flute\Core\App;

interface ServiceProviderInterface
{
    /**
     * Register services into the application.
     */
    public function register(\DI\ContainerBuilder $containerBuilder): void;

    /**
     * Bootstrap services into the application.
     */
    public function boot(\DI\Container $container): void;

    /**
     * Set the application instance.
     */
    public function setApp(App $app): void;
}
