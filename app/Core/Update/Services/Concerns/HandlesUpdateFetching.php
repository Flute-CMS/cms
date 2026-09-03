<?php

namespace Flute\Core\Update\Services\Concerns;

use Flute\Core\App;
use Flute\Core\Services\FluteApiClient;

trait HandlesUpdateFetching
{
    private ?array $freshUpdatesCache = null;
    private ?float $freshUpdatesFetchedAt = null;

    /**
     * Get available updates for all components
     */
    public function getAvailableUpdates(bool $forceRefresh = false): array
    {
        $cacheKey = self::CACHE_KEY . '_' . $this->channel . ( $this->useMockData ? '_mock' : '' );

        if ($forceRefresh) {
            cache()->delete($cacheKey);

            return $this->fetchUpdatesFromApi();
        }

        return cache()->callback($cacheKey, fn() => $this->fetchUpdatesFromApi(), self::CACHE_DURATION);
    }

    public function hasUpdate(string $type, ?string $identifier = null): bool
    {
        $updates = $this->getAvailableUpdates();

        if ($type === 'cms') {
            return !empty($updates['cms']);
        }

        return !empty($updates[$type . 's'][$identifier] ?? null);
    }

    public function getUpdateDetails(string $type, ?string $identifier = null): ?array
    {
        $updates = $this->getAvailableUpdates();

        if ($type === 'cms') {
            return $updates['cms'] ?? null;
        }

        return $updates[$type . 's'][$identifier] ?? null;
    }

    public function clearCache(): void
    {
        cache()->delete(self::CACHE_KEY . '_' . $this->channel);
        cache()->delete(self::CACHE_KEY . '_' . $this->channel . '_mock');
        cache()->delete(self::CACHE_KEY . '_catalog_stable');
        cache()->delete(self::CACHE_KEY . '_catalog_early');
        $this->freshUpdatesCache = null;
        $this->freshUpdatesFetchedAt = null;
    }

    /**
     * In-memory cache (4 min) so JWT tokens (TTL 5 min) stay valid across batch.
     */
    private function getFreshUpdatesForDownload(): array
    {
        $now = microtime(true);

        if (
            $this->freshUpdatesCache !== null
            && $this->freshUpdatesFetchedAt !== null
            && ( $now - $this->freshUpdatesFetchedAt ) < 240
        ) {
            return $this->freshUpdatesCache;
        }

        $this->freshUpdatesCache = $this->fetchUpdatesFromApi();
        $this->freshUpdatesFetchedAt = $now;

        return $this->freshUpdatesCache;
    }

    private function fetchUpdatesFromApi(): array
    {
        if ($this->useMockData) {
            return $this->parseMarkdownChangelogs($this->buildMockData());
        }

        try {
            $apiKey = config('app.flute_key');

            if (empty($apiKey)) {
                logs()->warning('Flute API key is empty. Can\'t fetch updates.');

                return [];
            }

            $api = new FluteApiClient(timeout: 10, connectTimeout: 5);

            $response = $api->get('/api/updates', [
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'Flute-CMS/' . App::VERSION,
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                ],
                'query' => [
                    'version' => App::VERSION,
                    'modules' => json_encode($this->getInstalledModules()),
                    'themes' => json_encode($this->getInstalledThemes()),
                    'accessKey' => $apiKey,
                    'phpVersion' => $this->getPHPVersion(),
                    'branch' => strtoupper($this->channel),
                    'nocache' => time(),
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);

                if (is_array($data)) {
                    return $this->parseMarkdownChangelogs($data);
                }
            }
        } catch (\Throwable $e) {
            logs()->error('Failed to fetch updates: ' . $e->getMessage());
        }

        return [];
    }

    private function parseMarkdownChangelogs(array $data): array
    {
        if (!empty($data['cms']) && is_array($data['cms'])) {
            if (!empty($data['cms']['changelog'])) {
                $data['cms']['changelog_html'] = $this->markdownParser->parse($data['cms']['changelog'], false, false);
            }

            if (!empty($data['cms']['previous_versions'])) {
                foreach ($data['cms']['previous_versions'] as $key => $version) {
                    if (!empty($version['changelog'])) {
                        $data['cms']['previous_versions'][$key]['changelog_html'] = $this->markdownParser->parse(
                            $version['changelog'],
                            false,
                            false,
                        );
                    }
                }
            }
        }

        if (!empty($data['modules']) && is_array($data['modules'])) {
            foreach ($data['modules'] as $moduleId => $module) {
                if (!empty($module['changelog'])) {
                    $data['modules'][$moduleId]['changelog_html'] = $this->markdownParser->parse(
                        $module['changelog'],
                        false,
                        false,
                    );
                }

                if (!empty($module['previous_versions'])) {
                    foreach ($module['previous_versions'] as $vKey => $version) {
                        if (!empty($version['changelog'])) {
                            $data['modules'][$moduleId]['previous_versions'][$vKey]['changelog_html'] = $this->markdownParser->parse(
                                $version['changelog'],
                                false,
                                false,
                            );
                        }
                    }
                }
            }
        }

        if (!empty($data['themes']) && is_array($data['themes'])) {
            foreach ($data['themes'] as $themeId => $theme) {
                if (!empty($theme['changelog'])) {
                    $data['themes'][$themeId]['changelog_html'] = $this->markdownParser->parse(
                        $theme['changelog'],
                        false,
                        false,
                    );
                }

                if (!empty($theme['previous_versions'])) {
                    foreach ($theme['previous_versions'] as $vKey => $version) {
                        if (!empty($version['changelog'])) {
                            $data['themes'][$themeId]['previous_versions'][$vKey]['changelog_html'] = $this->markdownParser->parse(
                                $version['changelog'],
                                false,
                                false,
                            );
                        }
                    }
                }
            }
        }

        return $data;
    }
}
