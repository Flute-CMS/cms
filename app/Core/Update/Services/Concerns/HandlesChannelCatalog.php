<?php

namespace Flute\Core\Update\Services\Concerns;

use Flute\Core\App;
use Flute\Core\Services\FluteApiClient;

trait HandlesChannelCatalog
{
    public function setChannel(string $channel): void
    {
        $this->channel = in_array($channel, ['stable', 'early'], true) ? $channel : 'stable';
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * Fetch updates for a specific channel without changing the active one
     */
    public function getUpdatesForChannel(string $channel, bool $forceRefresh = false): array
    {
        $original = $this->channel;
        $this->channel = in_array($channel, ['stable', 'early'], true) ? $channel : 'stable';

        try {
            return $this->getAvailableUpdates($forceRefresh);
        } finally {
            $this->channel = $original;
        }
    }

    /**
     * Fetch all available engine versions from /api/engine-updates.
     */
    public function getAllVersionsForChannel(string $channel): array
    {
        $channel = in_array($channel, ['stable', 'early'], true) ? $channel : 'stable';
        $cacheKey = self::CACHE_KEY . '_catalog_' . $channel;

        return cache()->callback($cacheKey, fn() => $this->fetchVersionCatalog($channel), self::CACHE_DURATION);
    }

    private function fetchVersionCatalog(string $channel): array
    {
        try {
            $apiKey = config('app.flute_key');

            if (empty($apiKey)) {
                return [];
            }

            $api = new FluteApiClient(timeout: 10, connectTimeout: 5);

            $response = $api->get('/api/engine-updates', [
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'Flute-CMS/' . App::VERSION,
                ],
                'query' => [
                    'channel' => strtoupper($channel),
                    'accessKey' => $apiKey,
                    'nocache' => time(),
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $versions = json_decode($response->getBody(), true);

            if (!is_array($versions) || empty($versions)) {
                return [];
            }

            $filtered = array_values(array_filter($versions, static fn(array $v) => $v['isPublic'] ?? false));

            if (empty($filtered)) {
                return [];
            }

            $latest = array_shift($filtered);

            $cms = [
                'version' => $latest['version'] ?? '',
                'current_version' => App::VERSION,
                'release_date' => isset($latest['releaseDate'])
                    ? date(default_date_format(true), strtotime($latest['releaseDate']))
                    : null,
                'changelog' => $latest['changelog'] ?? '',
                'download_url' => $latest['downloadUrl'] ?? '',
                'previous_versions' => [],
            ];

            if (!empty($cms['changelog'])) {
                $cms['changelog_html'] = $this->markdownParser->parse($cms['changelog'], false, false);
            }

            foreach ($filtered as $v) {
                $prev = [
                    'version' => $v['version'] ?? '',
                    'release_date' => isset($v['releaseDate'])
                        ? date(default_date_format(true), strtotime($v['releaseDate']))
                        : null,
                    'changelog' => $v['changelog'] ?? '',
                    'download_url' => $v['downloadUrl'] ?? '',
                ];

                if (!empty($prev['changelog'])) {
                    $prev['changelog_html'] = $this->markdownParser->parse($prev['changelog'], false, false);
                }

                $cms['previous_versions'][] = $prev;
            }

            return [
                'cms' => $cms,
                'modules' => [],
                'themes' => [],
            ];
        } catch (\Throwable $e) {
            logs()->error('Failed to fetch version catalog: ' . $e->getMessage());

            \Flute\Core\Services\CrashReportService::capture($e, ['source' => 'update.catalog']);
        }

        return [];
    }

    private function findCatalogVersionDownloadUrl(string $version): ?string
    {
        $catalog = $this->getAllVersionsForChannel($this->channel);
        $cms = $catalog['cms'] ?? null;

        if (!is_array($cms)) {
            return null;
        }

        if (( $cms['version'] ?? null ) === $version && !empty($cms['download_url'])) {
            return (string) $cms['download_url'];
        }

        $previous = $cms['previous_versions'] ?? [];
        if (!is_array($previous)) {
            return null;
        }

        foreach ($previous as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            if (( $entry['version'] ?? null ) === $version && !empty($entry['download_url'])) {
                return (string) $entry['download_url'];
            }
        }

        return null;
    }
}
