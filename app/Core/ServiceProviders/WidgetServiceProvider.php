<?php


namespace Flute\Core\ServiceProviders;

use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Widgets\WidgetManager;

class WidgetServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder) : void
    {
        $containerBuilder->addDefinitions([
            WidgetManager::class => \DI\autowire(),
            'widgetManager' => \DI\get(WidgetManager::class),
        ]);
    }

    public function boot(\DI\Container $container) : void
    {
        // Init widgets
        $container->get(WidgetManager::class);
    }
}
