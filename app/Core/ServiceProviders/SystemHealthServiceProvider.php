<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\SystemHealth\SystemHealthCheck;

class SystemHealthServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            SystemHealthCheck::class => \DI\create(),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
        if (function_exists('is_cli') && is_cli()) {
            $container->get(SystemHealthCheck::class)->run();

            return;
        }

        if (function_exists('is_admin_path') && !is_admin_path()) {
            return;
        }

        $container->get(SystemHealthCheck::class)->run();
    }
}
