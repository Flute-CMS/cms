<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Support\FluteEventDispatcher;
use Flute\Core\Template\Template;
use Flute\Core\Template\TemplateAssets;
use Flute\Core\Theme\Events\ThemeChangedEvent;
use Flute\Core\Theme\ThemeManager;

class ViewServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            TemplateAssets::class => \DI\create(),

            Template::class => \DI\autowire(),

            ThemeManager::class => \DI\autowire(),

            'flute.view' => \DI\get(Template::class),

            'flute.view.manager' => \DI\get(ThemeManager::class),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
        if (!is_cli()) {
            $container->get(FluteEventDispatcher::class)->addListener(ThemeChangedEvent::NAME, static function (ThemeChangedEvent $event) {
                app()->setTheme($event->getTheme());
            });
        }
    }
}
