<?php

namespace Flute\Core\ServiceProviders;

use DI\DependencyException;
use DI\NotFoundException;
use Flute\Core\Events\ResponseEvent;
use Flute\Core\Services\CookieService;
use Flute\Core\Support\AbstractServiceProvider;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CookieServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            CookieService::class => \DI\autowire(),
            "cookies" => \DI\get(CookieService::class),
        ]);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function boot(\DI\Container $container): void
    {
        if (!is_cli()) {
            $container->get(EventDispatcher::class)
                ->addListener(ResponseEvent::NAME, [$container->get(CookieService::class), 'onResponse']);
        }
    }
}
