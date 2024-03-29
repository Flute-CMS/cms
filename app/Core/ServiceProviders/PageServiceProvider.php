<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Page\PageEditorParser;
use Flute\Core\Page\PageManager;

use Flute\Core\Support\AbstractServiceProvider;

class PageServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder) : void
    {
        $containerBuilder->addDefinitions([
            PageManager::class => \DI\autowire(),
            PageEditorParser::class => \DI\create(),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
        is_installed() && $container->get(PageManager::class);
    }
}