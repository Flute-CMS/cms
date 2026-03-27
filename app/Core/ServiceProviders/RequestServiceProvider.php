<?php

namespace Flute\Core\ServiceProviders;

use DI\Container;
use DI\ContainerBuilder;
use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Support\FluteRequest;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RequestContext;

class RequestServiceProvider extends AbstractServiceProvider
{
    private const PRIVATE_SUBNET_CIDRS = [
        '127.0.0.1',
        '::1',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        'fc00::/7',
    ];

    private const DEFAULT_TRUSTED_HEADERS = [
        'X_FORWARDED_FOR',
        'X_FORWARDED_HOST',
        'X_FORWARDED_PROTO',
        'X_FORWARDED_PORT',
    ];

    private const TRUSTED_HEADER_MAP = [
        'FORWARDED' => Request::HEADER_FORWARDED,
        'X_FORWARDED_FOR' => Request::HEADER_X_FORWARDED_FOR,
        'X_FORWARDED_HOST' => Request::HEADER_X_FORWARDED_HOST,
        'X_FORWARDED_PROTO' => Request::HEADER_X_FORWARDED_PROTO,
        'X_FORWARDED_PORT' => Request::HEADER_X_FORWARDED_PORT,
        'X_FORWARDED_PREFIX' => Request::HEADER_X_FORWARDED_PREFIX,
    ];

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            FluteRequest::class => \DI\factory(static fn() => FluteRequest::createFromGlobals()),
            Request::class => \DI\get(FluteRequest::class),
            RequestInterface::class => \DI\get(FluteRequest::class),
            Response::class => \DI\create(),
            RequestStack::class => \DI\factory(static fn() => new RequestStack()),
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

        $trustedProxies = $this->resolveTrustedProxyList((array) config('app.trusted_proxies', [
            'AUTO',
        ]));
        $trustedHeaders = $this->resolveTrustedHeaderSet((array) config('app.trusted_headers', self::DEFAULT_TRUSTED_HEADERS));

        if ($trustedProxies !== []) {
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

    /**
     * @param list<string> $entries
     *
     * @return list<string>
     */
    private function resolveTrustedProxyList(array $entries): array
    {
        $entries = array_values(array_filter($entries, static fn($v) => is_string($v) && $v !== ''));

        // Explicit "trust no proxies" from config
        if ($entries === []) {
            return [];
        }

        $resolved = [];

        foreach ($entries as $entry) {
            $upper = strtoupper($entry);

            if ($upper === 'AUTO') {
                $resolved = array_merge($resolved, $this->resolveAutoTrustedProxies());

                continue;
            }

            if ($upper === 'REMOTE_ADDR') {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                if ($ip !== '') {
                    $resolved[] = $ip;
                }

                continue;
            }
            if ($entry === '*') {
                $resolved[] = $entry;
                continue;
            }
            $resolved[] = $entry;
        }

        return array_values(array_unique($resolved));
    }

    /**
     * Safe default:
     * - trust no proxy on direct/shared hosting requests;
     * - trust the immediate hop only when it is local/private and actually sends forwarding headers.
     *
     * @return list<string>
     */
    private function resolveAutoTrustedProxies(): array
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($remoteAddr === '') {
            return [];
        }

        if (!IpUtils::checkIp($remoteAddr, self::PRIVATE_SUBNET_CIDRS)) {
            return [];
        }

        foreach (self::DEFAULT_TRUSTED_HEADERS as $header) {
            $serverKey = 'HTTP_' . $header;
            if (!empty($_SERVER[$serverKey])) {
                return [$remoteAddr];
            }
        }

        return [];
    }

    private function resolveTrustedHeaderSet(array $entries): int
    {
        $entries = array_values(array_filter($entries, static fn($v) => is_string($v) && $v !== ''));

        if ($entries === []) {
            return Request::HEADER_X_FORWARDED_FOR;
        }

        $resolved = 0;

        foreach ($entries as $entry) {
            $upper = strtoupper($entry);

            if ($upper === 'AWS_ELB') {
                return Request::HEADER_X_FORWARDED_AWS_ELB;
            }

            if ($upper === 'TRAEFIK') {
                return Request::HEADER_X_FORWARDED_TRAEFIK;
            }

            if (isset(self::TRUSTED_HEADER_MAP[$upper])) {
                $resolved |= self::TRUSTED_HEADER_MAP[$upper];
            }
        }

        return $resolved !== 0 ? $resolved : Request::HEADER_X_FORWARDED_FOR;
    }
}
