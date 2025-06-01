<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\TracyBar\FluteTracyBar;
use Flute\Core\Support\AbstractServiceProvider;

class TracyBarServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            FluteTracyBar::class => \DI\autowire(FluteTracyBar::class),
            "TracyBar" => \DI\get(FluteTracyBar::class),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
        if (is_debug() && !is_cli())
            $container->get(FluteTracyBar::class);
    }
}
