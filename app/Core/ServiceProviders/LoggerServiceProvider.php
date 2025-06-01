<?php

namespace Flute\Core\ServiceProviders;

use DI\Container;

use Flute\Core\Services\LoggerService;
use Flute\Core\Support\AbstractServiceProvider;
use Psr\Log\LoggerInterface;
use DI\ContainerBuilder;

class LoggerServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            LoggerService::class => \DI\create(LoggerService::class)->constructor(\DI\get('logging.loggers')),
            'logger' => \DI\factory(function ($container) {
                return $container->get(LoggerService::class)->getLogger('flute');
            }),
            LoggerInterface::class => \DI\get('logger'),
        ]);
    }

    public function boot(Container $container): void
    {
        // Setup log rotation and cleanup cron job
        $container->get(LoggerService::class)->setupCron();
    }
}
