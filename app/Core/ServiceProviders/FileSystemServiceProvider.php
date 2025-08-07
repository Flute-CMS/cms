<?php

namespace Flute\Core\ServiceProviders;

use DI\Container;
use DI\ContainerBuilder;
use Flute\Core\Services\FileSystemService;
use Flute\Core\Support\AbstractServiceProvider;
use Symfony\Component\Finder\Finder;

class FileSystemServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            FileSystemService::class => \DI\autowire(FileSystemService::class),
            Finder::class => \DI\autowire(),
            'files' => \DI\get(FileSystemService::class),
        ]);
    }

    public function boot(Container $container): void
    {
        $container->get(FileSystemService::class)->importHelpers();
    }
}
