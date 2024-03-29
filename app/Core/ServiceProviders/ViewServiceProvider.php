<?php

namespace Flute\Core\ServiceProviders;


use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Theme\ThemeFinder;
use Flute\Core\Template\Template;
use Flute\Core\Template\TemplateAssets;
use Flute\Core\Theme\ThemeManager;

class ViewServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void {
        $definitions = [
            TemplateAssets::class => \DI\create(),

            Template::class => \DI\autowire(),

            ThemeManager::class => \DI\autowire(),

            // We are assuming that the "is_installed" is a environment configuration
            ThemeFinder::class => \DI\create(),
        ];

        $containerBuilder->addDefinitions($definitions);
    }

    public function boot(\DI\Container $container): void
    {
        if( !request()->expectsJson() && !request()->isAjax() )
            $container->get(ThemeManager::class);
    }
}