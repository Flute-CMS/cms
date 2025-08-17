<?php

namespace Flute\Core\Modules\Admin\Providers;

use Flute\Admin\AdminPackageFactory;
use Flute\Admin\AdminPanel;
use Flute\Core\Support\AbstractServiceProvider;

class AdminServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            AdminPanel::class => \DI\autowire(),
            AdminPackageFactory::class => \DI\autowire(),
        ]);
    }

    /**
     * Register auth service.
     */
    public function boot(\DI\Container $container): void
    {
        if (!is_installed() || !is_admin_path() || (is_admin_path() && !user()->can('admin'))) {
            return;
        }

        $container->get(AdminPanel::class)->initialize();
    }
}
