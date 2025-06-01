<?php

namespace Flute\Core\ServiceProviders;

use GO\Scheduler;
use DI\Container;
use Flute\Core\Support\AbstractServiceProvider;

class CronServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder) : void
    {
        $containerBuilder->addDefinitions([
            Scheduler::class => \DI\autowire(Scheduler::class),
        ]);
    }

    public function boot(Container $container) : void {}
}
