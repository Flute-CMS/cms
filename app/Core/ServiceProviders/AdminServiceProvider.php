<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Admin\AdminBuilder;
use Flute\Core\Services\AdminService;
use Flute\Core\Services\UserService;
use Flute\Core\Support\AbstractServiceProvider;

class AdminServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            AdminBuilder::class => \DI\autowire(AdminBuilder::class),
            AdminService::class => \DI\autowire(),
        ]);
    }

    /**
     * Register auth service.
     */
    public function boot(\DI\Container $container): void
    {
        if (!is_installed())
            return;

        $this->loadRoutesFrom('app/Core/Admin/routes.php');

        /** 
         * Инициализация Builder'а в системе.
         * 
         * В целях улучшения произ-ти сделана проверка на права админ-панели.
         */
        $container->get(UserService::class)->hasPermission('admin')
            && $container->get(AdminBuilder::class);
    }
}