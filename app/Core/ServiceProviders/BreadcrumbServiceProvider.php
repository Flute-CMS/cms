<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Services\BreadcrumbService;

use Flute\Core\Support\AbstractServiceProvider;

class BreadcrumbServiceProvider extends AbstractServiceProvider
{
    public function register( \DI\ContainerBuilder $containerBuilder ): void
    {
        $containerBuilder->addDefinitions([
            BreadcrumbService::class => \DI\create(),
            "breadcrumb" => \DI\get(BreadcrumbService::class)
        ]);
    }

    public function boot( \DI\Container $container ): void
    {}
}
