<?php

namespace Flute\Core\ServiceProviders;

use DI\Container;
use DI\ContainerBuilder;
use Flute\Core\Services\LoggerService;
use Flute\Core\Support\AbstractServiceProvider;
use Psr\Log\LoggerInterface;

class LoggerServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            LoggerService::class => \DI\create(LoggerService::class)->constructor(\DI\get('logging.loggers')),
            'logger' => \DI\factory(static fn ($container) => $container->get(LoggerService::class)->getLogger('flute')),
            LoggerInterface::class => \DI\get('logger'),
        ]);
    }

    public function boot(Container $container): void
    {
        $container->get(LoggerService::class)->setupCron();
    }
}
