<?php

namespace Flute\Core\ServiceProviders;

use DI\Container;
use DI\ContainerBuilder;
use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Support\FluteRequest;
use Psr\Http\Message\RequestInterface;
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

    private const HEADER_FORWARDED = 0b000001;
    private const HEADER_X_FORWARDED_FOR = 0b000010;
    private const HEADER_X_FORWARDED_HOST = 0b000100;
    private const HEADER_X_FORWARDED_PROTO = 0b001000;
    private const HEADER_X_FORWARDED_PORT = 0b010000;
    private const HEADER_X_FORWARDED_PREFIX = 0b100000;
    private const HEADER_X_FORWARDED_AWS_ELB = 0b0011010;
    private const HEADER_X_FORWARDED_TRAEFIK = 0b0111110;

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

        $trustedProxyEntries = $this->readConfigStringList('app.trusted_proxies', ['AUTO']);
        $trustedHeaderEntries = $this->readConfigStringList('app.trusted_headers', self::DEFAULT_TRUSTED_HEADERS);

        $trustedProxies = $this->resolveTrustedProxyList($trustedProxyEntries);
        $trustedHeaders = $this->resolveTrustedHeaderSet($trustedHeaderEntries);

        if ($trustedProxies !== []) {
            FluteRequest::setTrustedProxies($trustedProxies, $trustedHeaders);
            Request::setTrustedProxies($trustedProxies, $trustedHeaders);
        } else {
            FluteRequest::setTrustedProxies([], self::HEADER_X_FORWARDED_FOR);
            Request::setTrustedProxies([], self::HEADER_X_FORWARDED_FOR);
        }

        $trustedHosts = $this->readConfigStringList('app.trusted_hosts', []);
        $appUrl = $this->readConfigString('app.url', '');

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
     */
    private function resolveTrustedProxyList(array $entries): array
    {
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

        if (!$this->isPrivateOrLocalAddress($remoteAddr)) {
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

    /**
     * @param list<string> $entries
     */
    private function resolveTrustedHeaderSet(array $entries): int
    {
        if ($entries === []) {
            return self::HEADER_X_FORWARDED_FOR;
        }

        $resolved = 0;

        foreach ($entries as $entry) {
            $upper = strtoupper($entry);

            if ($upper === 'AWS_ELB') {
                return self::HEADER_X_FORWARDED_AWS_ELB;
            }

            if ($upper === 'TRAEFIK') {
                return self::HEADER_X_FORWARDED_TRAEFIK;
            }

            $headerFlag = match ($upper) {
                'FORWARDED' => self::HEADER_FORWARDED,
                'X_FORWARDED_FOR' => self::HEADER_X_FORWARDED_FOR,
                'X_FORWARDED_HOST' => self::HEADER_X_FORWARDED_HOST,
                'X_FORWARDED_PROTO' => self::HEADER_X_FORWARDED_PROTO,
                'X_FORWARDED_PORT' => self::HEADER_X_FORWARDED_PORT,
                'X_FORWARDED_PREFIX' => self::HEADER_X_FORWARDED_PREFIX,
                default => 0,
            };

            if ($headerFlag !== 0) {
                $resolved |= $headerFlag;
            }
        }

        return $resolved !== 0 ? $resolved : self::HEADER_X_FORWARDED_FOR;
    }

    private function isPrivateOrLocalAddress(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            if (str_starts_with($ip, '10.') || str_starts_with($ip, '127.') || str_starts_with($ip, '192.168.')) {
                return true;
            }

            if (!str_starts_with($ip, '172.')) {
                return false;
            }

            $parts = explode('.', $ip, 3);
            $secondOctet = isset($parts[1]) ? (int) $parts[1] : -1;

            return $secondOctet >= 16 && $secondOctet <= 31;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            return false;
        }

        $normalized = strtolower($ip);

        return $normalized === '::1' || str_starts_with($normalized, 'fc') || str_starts_with($normalized, 'fd');
    }

    /**
     * @param list<string> $default
     *
     * @return list<string>
     */
    private function readConfigStringList(string $key, array $default): array
    {
        if (!function_exists('config')) {
            return $default;
        }

        /** @var mixed $value */
        $value = call_user_func('config', $key, $default);

        if (!is_array($value)) {
            return $default;
        }

        $result = [];

        foreach ($value as $item) {
            if (!is_string($item) || $item === '') {
                continue;
            }

            $result[] = $item;
        }

        return $result;
    }

    private function readConfigString(string $key, string $default): string
    {
        if (!function_exists('config')) {
            return $default;
        }

        /** @var mixed $value */
        $value = call_user_func('config', $key, $default);

        return is_string($value) ? $value : $default;
    }
}
