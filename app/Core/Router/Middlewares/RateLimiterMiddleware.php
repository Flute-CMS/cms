<?php

namespace Flute\Core\Router\Middlewares;

use Closure;
use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Throwable;

class RateLimiterMiddleware extends BaseMiddleware
{
    protected RateLimiterFactory $rateLimiterFactory;

    protected ?\Symfony\Contracts\Cache\CacheInterface $cache;

    public function __construct(RateLimiterFactory $rateLimiterFactory, ?\Symfony\Contracts\Cache\CacheInterface $cache = null)
    {
        $this->rateLimiterFactory = $rateLimiterFactory;
        $this->cache = $cache;
    }

    public function handle(FluteRequest $request, Closure $next, ...$args): Response
    {
        $config = config('rate_limit', [
            'enabled' => true,
            'policy' => 'fixed_window',
            'limit' => 60,
            'interval' => '1 minute',
            'by' => 'ip',
            'headers' => true,
            'key_prefix' => 'rl:',
            'hash_keys' => true,
            'rate' => [
                'amount' => 1,
            ],
        ]);

        if (!(bool) ($config['enabled'] ?? true)) {
            return $next($request);
        }

        if (in_array($request->getMethod(), ['OPTIONS', 'HEAD'])) {
            return $next($request);
        }

        $key = $this->getLimiterKey($request, $args, $config);

        $limitOverride = isset($args[1]) && is_numeric($args[1]) ? (int) $args[1] : null;
        $intervalOverride = $args[2] ?? null; // seconds or interval string
        $policyOverride = $args[3] ?? null; // 'fixed_window'|'sliding_window'|'token_bucket'

        $weight = isset($args[4]) && is_numeric($args[4]) ? (int) $args[4] : 1;

        if ($limitOverride !== null || $intervalOverride !== null || $policyOverride !== null) {
            $interval = $intervalOverride;
            if ($interval !== null && is_numeric($interval)) {
                $interval = (int) $interval . ' seconds';
            }

            $policy = $policyOverride ?: ($config['policy'] ?? 'fixed_window');
            $opts = [
                'id' => 'route:' . $this->routeNameOrPath(),
                'policy' => $policy,
            ];

            $tbAmount = isset($args[5]) && is_numeric($args[5]) ? (int) $args[5] : 1;

            if ($policy === 'token_bucket') {
                $opts['limit'] = $limitOverride ?? ($config['limit'] ?? 60); // capacity
                $opts['rate'] = [
                    'interval' => $interval ?: ($config['interval'] ?? '1 minute'),
                    'amount' => $tbAmount,
                ];
            } else {
                $opts['limit'] = $limitOverride ?? ($config['limit'] ?? 60);
                $opts['interval'] = $interval ?: ($config['interval'] ?? '1 minute');
            }

            $storage = $this->cache
                ? new \Symfony\Component\RateLimiter\Storage\CacheStorage($this->cache)
                :
                throw new RuntimeException('RateLimiter: cache storage is required in production');

            $factory = new \Symfony\Component\RateLimiter\RateLimiterFactory($opts, $storage);
        } else {
            $factory = $this->rateLimiterFactory;
        }

        $limiter = $factory->create($key);

        $limit = $limiter->consume($weight);

        if (!$limit->isAccepted()) {
            return $this->tooMany($limit, $config);
        }

        $response = $next($request);

        if (($config['headers'] ?? true) && (method_exists($response, 'withHeaders') || property_exists($response, 'headers'))) {
            $this->attachHeaders($response, $limit);
        }

        return $response;
    }

    protected function getLimiterKey(FluteRequest $request, array $args, array $config): string
    {
        $strategy = $args[0] ?? ($config['by'] ?? 'ip');
        $prefix = $config['key_prefix'] ?? 'rl:';
        $route = $this->routeNameOrPath();

        if ($strategy === 'user') {
            $subject = $request->user() ? (string) $request->user()->id : $request->getClientIp();
        } elseif ($strategy === 'ip_user') {
            $subject = $request->getClientIp() . ':' . ($request->user()->id ?? 'guest');
        } else {
            $subject = $request->getClientIp();
        }

        if (($config['hash_keys'] ?? true)) {
            $algo = \in_array('xxh3', hash_algos(), true) ? 'xxh3' : 'sha256';
            $subject = hash($algo, $subject);
        }

        return "{$prefix}{$strategy}:{$route}:{$subject}";
    }

    protected function routeNameOrPath(): string
    {
        try {
            $route = router()->getCurrentRoute();

            return $route?->getName() ?: trim(request()->getPathInfo(), '/') ?: 'root';
        } catch (Throwable) {
            return trim(request()->getPathInfo(), '/') ?: 'root';
        }
    }

    protected function attachHeaders($response, $limit): void
    {
        $limitTotal = method_exists($limit, 'getLimit') ? $limit->getLimit() : (int) config('rate_limit.limit', 60);
        $remaining = method_exists($limit, 'getRemainingTokens') ? $limit->getRemainingTokens() : null;

        $headers = ['X-RateLimit-Limit' => (string) $limitTotal];
        if ($remaining !== null) {
            $headers['X-RateLimit-Remaining'] = (string) max(0, $remaining);
        }

        $reset = method_exists($limit, 'getRetryAfter') ? $limit->getRetryAfter() : null;
        if ($reset) {
            $seconds = max(1, $reset->getTimestamp() - time());
            $headers['Retry-After'] = (string) $seconds;
            $headers['X-RateLimit-Reset'] = (string) $reset->getTimestamp();
        }

        $policy = method_exists($limit, 'getPolicy') ? $limit->getPolicy() : null;
        if ($policy) {
            $headers['X-RateLimit-Policy'] = $policy;
        }

        if (method_exists($response, 'withHeaders')) {
            $response->withHeaders($headers);
        } elseif (property_exists($response, 'headers')) {
            foreach ($headers as $k => $v) {
                $response->headers->set($k, $v);
            }
        }
    }

    protected function tooMany($limit, array $config): Response
    {
        $retryAfter = method_exists($limit, 'getRetryAfter') ? $limit->getRetryAfter() : null;
        $seconds = $retryAfter ? max(1, $retryAfter->getTimestamp() - time()) : 60;

        $resp = $this->error()->tooManyRequests();

        $this->attachHeaders($resp, $limit);

        if (property_exists($resp, 'headers')) {
            $resp->headers->set('Retry-After', (string) $seconds);
            if ($retryAfter) {
                $resp->headers->set('X-RateLimit-Reset', (string) $retryAfter->getTimestamp());
            }
        }

        return $resp;
    }
}
