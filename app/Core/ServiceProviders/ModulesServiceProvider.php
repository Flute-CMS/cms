<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Modules\ModuleFinder;
use Flute\Core\Modules\ModuleManager;
use Flute\Core\Modules\ModuleRegister;
use Flute\Core\Modules\ModuleActions;

use Flute\Core\Support\AbstractServiceProvider;

class ModulesServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder) : void
    {
        $containerBuilder->addDefinitions([
            ModuleFinder::class => \DI\create(),
            ModuleRegister::class => \DI\create(),
            ModuleManager::class => \DI\autowire(),
            ModuleActions::class => \DI\create(),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
        $container->get(ModuleManager::class);
    }
}