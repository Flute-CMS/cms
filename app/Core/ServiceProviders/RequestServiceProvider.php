<?php

namespace Flute\Core\ServiceProviders;

use DI\Container;

use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Support\FluteRequest;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RequestContext;
use DI\ContainerBuilder;

class RequestServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            FluteRequest::class => \DI\factory(function () {
                return FluteRequest::createFromGlobals();
            }),
            Request::class => \DI\get(FluteRequest::class),
            RequestInterface::class => \DI\get(FluteRequest::class),
            Response::class => \DI\create(),
            RequestContext::class => \DI\factory(function (Container $container) {
                $context = new RequestContext();
                $context->fromRequest($container->get(Request::class));
                return $context;
            })
        ]);
    }

    public function boot(Container $container): void
    {
        if (!is_cli()) {
            FluteRequest::setTrustedProxies(
                ['REMOTE_ADDR'],
                Request::HEADER_X_FORWARDED_FOR
            );
            Request::setTrustedProxies(
                ['REMOTE_ADDR'],
                Request::HEADER_X_FORWARDED_FOR
            );
        }
    }
}
