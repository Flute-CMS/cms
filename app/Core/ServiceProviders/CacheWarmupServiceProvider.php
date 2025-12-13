<?php

namespace Flute\Core\ServiceProviders;

use DI\Container;
use DI\ContainerBuilder;
use Flute\Core\Services\CacheWarmupService;
use Flute\Core\Support\AbstractServiceProvider;
use GO\Scheduler;

class CacheWarmupServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            CacheWarmupService::class => \DI\autowire(),
        ]);
    }

    public function boot(Container $container): void
    {
        $container->get(CacheWarmupService::class)->setupCron($container->get(Scheduler::class));
    }
}
