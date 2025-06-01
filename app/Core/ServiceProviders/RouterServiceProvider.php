<?php

namespace Flute\Core\ServiceProviders;

use DI\Container;
use DI\ContainerBuilder;
use Flute\Core\Router\ActiveRouter\ActiveRouter;
use Flute\Core\Router\ContainerControllerResolver;
use Flute\Core\Router\Contracts\RouterInterface;
use Flute\Core\Router\Router;
use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class RouterServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the services provided by the service provider.
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            RouteCollection::class => \DI\create(),

            RequestContext::class => \DI\autowire(),

            UrlMatcher::class => \DI\factory(function (Container $c) {
                return new UrlMatcher($c->get(RouteCollection::class), (new RequestContext())->fromRequest($c->get(FluteRequest::class)));
            }),

            ContainerControllerResolver::class => \DI\autowire(),

            UrlGenerator::class => \DI\factory(function (Container $c) {
                return new UrlGenerator($c->get(RouteCollection::class), (new RequestContext())->fromRequest($c->get(FluteRequest::class)));
            }),

            RateLimiterFactory::class => \DI\factory(function (Container $c) {
                $storage = new InMemoryStorage();

                $config = [
                    'id' => 'global',
                    'policy' => 'fixed_window',
                    'limit' => 100,
                    'interval' => '1 minute',
                ];

                return new RateLimiterFactory($config, $storage);
            }),

            CsrfTokenManagerInterface::class => \DI\factory(function (Container $c) {
                return new CsrfTokenManager();
            }),

            Router::class => \DI\autowire(),

            RouterInterface::class => \DI\get(Router::class),

            'router' => \DI\get(Router::class),

            ActiveRouter::class => \DI\autowire(),

            'active_router' => \DI\get(ActiveRouter::class),
        ]);
    }

    public function boot(Container $container): void
    {
    }
}
