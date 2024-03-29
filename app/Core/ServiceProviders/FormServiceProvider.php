<?php

namespace Flute\Core\ServiceProviders;


use Flute\Core\Services\FormService;
use Flute\Core\Support\AbstractServiceProvider;

class FormServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the services provided by the service provider.
     */
    public function register( \DI\ContainerBuilder $containerBuilder ): void
    {
        $containerBuilder->addDefinitions([
            FormService::class => \DI\create(),
            "form" => \DI\get(FormService::class)
        ]);
    }

    public function boot(\DI\Container $container) : void {}
}
