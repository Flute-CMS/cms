<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\ModulesManager\ModuleActions;
use Flute\Core\ModulesManager\ModuleFinder;
use Flute\Core\ModulesManager\ModuleManager;
use Flute\Core\ModulesManager\ModuleRegister;
use Flute\Core\Support\AbstractServiceProvider;

class ModulesServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
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
        $container->get(\Flute\Core\Database\DatabaseConnection::class)->recompileIfNeeded();
        $container->get(ModuleManager::class)->initialize();
    }
}
