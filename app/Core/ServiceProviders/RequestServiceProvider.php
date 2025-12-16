<?php

namespace Flute\Core\ServiceProviders;

use DI\Container;
use DI\ContainerBuilder;
use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Support\FluteRequest;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RequestContext;

class RequestServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            FluteRequest::class => \DI\factory(static fn () => FluteRequest::createFromGlobals()),
            Request::class => \DI\get(FluteRequest::class),
            RequestInterface::class => \DI\get(FluteRequest::class),
            Response::class => \DI\create(),
            RequestContext::class => \DI\factory(static function (Container $container) {
                $context = new RequestContext();
                $context->fromRequest($container->get(Request::class));

                return $context;
            }),
        ]);
    }

    public function boot(Container $container): void
    {
        if (is_cli()) {
            return;
        }

        $trustedProxies = array_filter((array) config('app.trusted_proxies', []));

        if (in_array('*', $trustedProxies, true) || in_array('REMOTE_ADDR', $trustedProxies, true)) {
            $trustedProxies = [$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'];
        }

        $trustedHeaders = Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_HOST;

        if (!empty($trustedProxies)) {
            FluteRequest::setTrustedProxies($trustedProxies, $trustedHeaders);
            Request::setTrustedProxies($trustedProxies, $trustedHeaders);
        } else {
            FluteRequest::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR);
            Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR);
        }

        $trustedHosts = array_filter((array) config('app.trusted_hosts', []));
        $appUrl = config('app.url');

        if ($appUrl) {
            $appHost = parse_url($appUrl, PHP_URL_HOST);
            if ($appHost) {
                $trustedHosts[] = '^' . preg_quote($appHost, '#') . '$';
            }
        }

        $trustedHosts = array_values(array_unique($trustedHosts));

        if (!empty($trustedHosts)) {
            FluteRequest::setTrustedHosts($trustedHosts);
            Request::setTrustedHosts($trustedHosts);
        }
    }
}
