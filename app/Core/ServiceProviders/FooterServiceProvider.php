<?php 

namespace Flute\Core\ServiceProviders;

use Flute\Core\Services\FooterService;

use Flute\Core\Support\AbstractServiceProvider;

class FooterServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            FooterService::class => \DI\create(),
            "footer" => \DI\get(FooterService::class)
        ]);
    }

    public function boot(\DI\Container $container) : void {}
}
