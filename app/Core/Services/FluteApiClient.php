<?php

declare(strict_types = 1);

namespace Flute\Core\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * Centralized HTTP client for Flute API with automatic mirror failover.
 *
 * When the primary endpoint is unreachable, the client transparently
 * retries the same request against a list of mirror URLs. The first
 * mirror that responds successfully is promoted for subsequent calls
 * within the same request lifecycle.
 */
class FluteApiClient
{
    protected array $mirrors;
    protected string $apiKey;
    protected Client $client;

    /** Mirror that responded successfully — promoted for this request. */
    protected ?string $activeMirror = null;

    /** Cache key used to remember a working mirror across requests. */
    private const MIRROR_CACHE_KEY = 'flute_api_active_mirror';

    private const MIRROR_CACHE_TTL = 300; // 5 min

    public function __construct(
        float $timeout = 10,
        float $connectTimeout = 5,
    ) {
        $primary = rtrim(config('app.flute_market_url', 'https://flute-cms.com'), '/');
        $this->apiKey = config('app.flute_key', '');

        $configured = config('app.flute_market_mirrors', []);

        $this->mirrors = array_values(array_unique(array_merge(
            [$primary],
            is_array($configured) ? $configured : [],
            $this->getDefaultMirrors($primary),
        )));

        // Promote cached working mirror to the front.
        try {
            $cached = cache()->get(self::MIRROR_CACHE_KEY);
            if ($cached && $cached !== $this->mirrors[0]) {
                $this->mirrors = array_values(array_unique(array_merge([$cached], $this->mirrors)));
            }
        } catch (\Throwable) {
        }

        $this->client = new Client([
            'timeout' => $timeout,
            'connect_timeout' => $connectTimeout,
            'http_errors' => false,
        ]);
    }

    /**
     * GET request with automatic mirror failover.
     *
     * @throws \Exception when all mirrors fail
     */
    public function get(string $path, array $options = []): ResponseInterface
    {
        return $this->requestWithFailover('GET', $path, $options);
    }

    /**
     * POST request with automatic mirror failover.
     *
     * @throws \Exception when all mirrors fail
     */
    public function post(string $path, array $options = []): ResponseInterface
    {
        return $this->requestWithFailover('POST', $path, $options);
    }

    /**
     * GET request; returns decoded JSON body or throws.
     *
     * @throws \Exception on network / HTTP error
     */
    public function getJson(string $path, array $query = []): array
    {
        $response = $this->get($path, ['query' => $query]);

        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        if ($statusCode !== 200) {
            $error = json_decode($body, true);
            throw new \Exception($error['error'] ?? $body);
        }

        return json_decode($body, true) ?? [];
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Returns the mirror that is currently responding, or primary.
     */
    public function getActiveBaseUrl(): string
    {
        return $this->activeMirror ?? $this->mirrors[0];
    }

    /**
     * Raw Guzzle client (for sink/download — caller handles mirrors).
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    // ---------------------------------------------------------------

    protected function requestWithFailover(string $method, string $path, array $options): ResponseInterface
    {
        $lastException = null;

        foreach ($this->mirrors as $baseUrl) {
            $url = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');

            try {
                $response = $this->client->request($method, $url, $options);

                $code = $response->getStatusCode();

                // Treat 502/503/504 as "mirror is down" and try next.
                if (in_array($code, [502, 503, 504], true)) {
                    $lastException = new \Exception("HTTP {$code} from {$baseUrl}");
                    continue;
                }

                // This mirror works — remember it.
                if ($this->activeMirror !== $baseUrl) {
                    $this->activeMirror = $baseUrl;
                    $this->cacheMirror($baseUrl);
                }

                return $response;
            } catch (GuzzleException $e) {
                logs()->warning("Flute API mirror failed ({$baseUrl}): " . $e->getMessage());
                $lastException = $e;
            }
        }

        throw new \Exception(
            'All Flute API mirrors are unavailable: '
            . ( $lastException ? $lastException->getMessage() : 'unknown error' ),
        );
    }

    private function cacheMirror(string $baseUrl): void
    {
        try {
            cache()->set(self::MIRROR_CACHE_KEY, $baseUrl, self::MIRROR_CACHE_TTL);
        } catch (\Throwable) {
        }
    }

    private function getDefaultMirrors(string $primary): array
    {
        $defaults = [
            'https://flute-cms.com',
            'https://api.flute-cms.com',
            'https://market.flute-cms.com',
        ];

        return array_filter($defaults, static fn(string $url): bool => rtrim($url, '/') !== $primary);
    }
}
