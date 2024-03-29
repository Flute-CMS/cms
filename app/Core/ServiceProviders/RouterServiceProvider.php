<?php

namespace Flute\Core\ServiceProviders;

use DI\Container;
use DI\ContainerBuilder;

use Flute\Core\Router\RouteDispatcher;
use Flute\Core\Support\AbstractServiceProvider;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class RouterServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the services provided by the service provider.
     */
    public function register( ContainerBuilder $containerBuilder ): void
    {
        $containerBuilder->addDefinitions([
            RouteCollection::class => \DI\create(),

            UrlMatcher::class => \DI\factory(function (Container $c) {
                return new UrlMatcher($c->get(RouteCollection::class), $c->get(RequestContext::class));
            }),

            ControllerResolver::class => \DI\autowire(),

            RouteDispatcher::class => \DI\autowire(),

            'router' => \DI\get(RouteDispatcher::class),
        ]);
    }

    public function boot(Container $container): void {}
}
