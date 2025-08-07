<?php

namespace Flute\Core\Contracts;

use Flute\Core\App;

interface ServiceProviderInterface
{
    /**
     * Register services into the application.
     * 
     * @param \DI\ContainerBuilder $containerBuilder
     * 
     * @return void
     */
    public function register( \DI\ContainerBuilder $containerBuilder ) : void;

    /**
     * Bootstrap services into the application.
     * 
     * @param \DI\Container $container
     * 
     * @return void
     */
    public function boot( \DI\Container $container ) : void;

    /**
     * Set the application instance.
     * 
     * @param App $app
     * 
     * @return void
     */
    public function setApp(App $app): void;
}