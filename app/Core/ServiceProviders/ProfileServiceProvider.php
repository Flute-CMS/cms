<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Profile\Mods\DevicesMode;
use Flute\Core\Profile\Mods\InvoicesMode;
use Flute\Core\Profile\Mods\MainMode;
use Flute\Core\Profile\Mods\SecurityMode;
use Flute\Core\Profile\Mods\SocialMode;
use Flute\Core\Profile\Mods\ThemeMode;
use Flute\Core\Profile\ProfileRoutes;
use Flute\Core\Profile\Tabs\MainTab;
use Flute\Core\Services\ProfileService;

use Flute\Core\Support\AbstractServiceProvider;

class ProfileServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder) : void
    {
        $containerBuilder->addDefinitions([
            ProfileService::class => \DI\create(),
            ProfileRoutes::class => \DI\create(),
            MainMode::class => \DI\create(),
            DevicesMode::class => \DI\create(),
            SocialMode::class => \DI\create(),
            InvoicesMode::class => \DI\create(),
            ThemeMode::class => \DI\create(),
            MainTab::class => \DI\create(),
        ]);
    }

    public function boot(\DI\Container $container) : void
    {
        if( is_installed() )
        {
            $service = $container->get(ProfileService::class);
            
            $service->addMod( $container->get( MainMode::class ) );
            $service->addMod( $container->get( SocialMode::class ) );
            $service->addMod( $container->get( SecurityMode::class ) );
            $service->addMod( $container->get( DevicesMode::class ) );
            $service->addMod( $container->get( InvoicesMode::class ) );
            $service->addMod( $container->get( ThemeMode::class ) );

            $service->addTab( $container->get( MainTab::class ) );

            $profileRoutes = $container->get(ProfileRoutes::class);
            
            $profileRoutes->register();
        }
    }
}