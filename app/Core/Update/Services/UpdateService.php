<?php

namespace Flute\Core\Update\Services;

use Flute\Core\App;
use Flute\Core\Markdown\Parser;
use Flute\Core\ModulesManager\ModuleManager;
use Flute\Core\Services\FluteApiClient;
use Flute\Core\Theme\ThemeManager;
use Flute\Core\Update\Updaters\ModuleUpdater;
use Flute\Core\Update\Updaters\ThemeUpdater;
use GuzzleHttp\Exception\GuzzleException;
use Throwable;

class UpdateService
{
    /**
     * Cache key for storing update information
     */
    private const CACHE_KEY = 'flute_updates';

    /**
     * Cache duration in minutes
     */
    private const CACHE_DURATION = 1440; // 1 day

    /**
     */
    protected ModuleManager $moduleManager;

    /**
     */
    protected ThemeManager $themeManager;

    /**
     */
    protected bool $useMockData = false;

    /**
     * Selected update channel: stable|early
     */
    protected string $channel = 'stable';

    /**
     */
    protected Parser $markdownParser;

    /**
     * UpdateService constructor.
     */
    public function __construct(
        ModuleManager $moduleManager,
        ThemeManager $themeManager,
        ?Parser $markdownParser = null,
    ) {
        $this->moduleManager = $moduleManager;
        $this->themeManager = $themeManager;
        $this->markdownParser = $markdownParser ?? new Parser();

        // if (config('app.debug')) {
        //     $this->useMockData = true;
        // }
    }

    /**
     * Set update channel
     */
    public function setChannel(string $channel): void
    {
        $this->channel = in_array($channel, ['stable', 'early'], true) ? $channel : 'stable';
    }

    /**
     * Get current channel
     */
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
     * Fetch all available engine versions from /api/engine-updates,
     * filtered by channel, and formatted to match the update card structure.
     */
    public function getAllVersionsForChannel(string $channel): array
    {
        $channel = in_array($channel, ['stable', 'early'], true) ? $channel : 'stable';
        $cacheKey = self::CACHE_KEY . '_catalog_' . $channel;

        return cache()->callback(
            $cacheKey,
            function () use ($channel) {
                return $this->fetchVersionCatalog($channel);
            },
            self::CACHE_DURATION,
        );
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
        }

        return [];
    }

    /**
     * Enable or disable mock data (for previews/tests)
     */
    public function enableMockData(bool $enable): void
    {
        $this->useMockData = $enable;
    }

    /**
     * Get available updates for all components
     *
     * @param bool $forceRefresh Принудительно обновить кэш
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

    /**
     * Check if updates are available for a specific component
     *
     * @param string $type cms|module|theme
     * @param string|null $identifier Component identifier
     */
    public function hasUpdate(string $type, ?string $identifier = null): bool
    {
        $updates = $this->getAvailableUpdates();

        if ($type === 'cms') {
            return !empty($updates['cms']);
        }

        return !empty($updates[$type . 's'][$identifier] ?? null);
    }

    /**
     * Get update details for a specific component
     *
     * @param string $type cms|module|theme
     * @param string|null $identifier Component identifier
     */
    public function getUpdateDetails(string $type, ?string $identifier = null): ?array
    {
        $updates = $this->getAvailableUpdates();

        if ($type === 'cms') {
            return $updates['cms'] ?? null;
        }

        return $updates[$type . 's'][$identifier] ?? null;
    }

    /**
     * Clear update cache
     */
    public function clearCache(): void
    {
        cache()->delete(self::CACHE_KEY . '_' . $this->channel);
        cache()->delete(self::CACHE_KEY . '_' . $this->channel . '_mock');
        cache()->delete(self::CACHE_KEY . '_catalog_stable');
        cache()->delete(self::CACHE_KEY . '_catalog_early');

        $this->getAvailableUpdates(true);

        // if (function_exists('opcache_reset')) {
        //     opcache_reset();
        // }
    }

    /**
     * Download update package
     *
     * @return string|null Path to downloaded file or null on failure
     */
    public function downloadUpdate(string $type, ?string $identifier = null, ?string $version = null): ?string
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }

        try {
            $updates = $this->getAvailableUpdates();

            $downloadUrl = null;
            $latestVersion = null;

            if ($type === 'cms') {
                $downloadUrl = $updates['cms']['download_url'] ?? null;
                $latestVersion = $updates['cms']['version'] ?? null;
            } elseif (!empty($identifier)) {
                $downloadUrl = $updates[$type . 's'][$identifier]['download_url'] ?? null;
                $latestVersion = $updates[$type . 's'][$identifier]['version'] ?? null;
            }

            if (empty($downloadUrl)) {
                logs()->error("Download URL not found for {$type} " . ( $identifier ?? '' ));

                return null;
            }

            $tempDir = storage_path('app/temp/updates');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0o755, true);
            }

            $fileName = $tempDir . '/' . ( $identifier ?? 'cms' ) . '-' . ( $version ?? $latestVersion ) . '.zip';

            $api = new FluteApiClient(timeout: 120, connectTimeout: 10);

            $fullUrl = $downloadUrl;
            if (!preg_match('/^https?:\/\//', $downloadUrl)) {
                $fullUrl =
                    rtrim($api->getActiveBaseUrl(), '/') . '/' . ltrim(str_replace('api/', '', $downloadUrl), '/');
            }

            // parse ?token safely
            $parsedDownloadUrl = parse_url($downloadUrl);
            $queryParams = [];
            if (isset($parsedDownloadUrl['query'])) {
                parse_str($parsedDownloadUrl['query'], $queryParams);
            }
            $token = $queryParams['token'] ?? '';

            $api->getClient()->request('GET', $fullUrl, [
                'headers' => [
                    'User-Agent' => 'Flute-CMS/' . App::VERSION,
                ],
                'sink' => $fileName,
                'query' => [
                    'accessKey' => config('app.flute_key'),
                    'versionId' => $version ?? $latestVersion,
                    'token' => $token,
                ],
            ]);

            if (!file_exists($fileName) || mime_content_type($fileName) !== 'application/zip') {
                logs()->error("Downloaded file is not a valid ZIP archive: {$fileName}");
                @unlink($fileName);

                return null;
            }

            return $fileName;
        } catch (GuzzleException $e) {
            logs()->error('Failed to download update: ' . $e->getMessage());

            if (is_debug()) {
                throw $e;
            }
        } catch (Throwable $e) {
            logs()->error('Error processing update download: ' . $e->getMessage());

            if (is_debug()) {
                throw $e;
            }
        }

        return null;
    }

    /**
     * Download a specific CMS version from the engine catalog.
     *
     * @return string|null Path to downloaded file
     */
    public function downloadVersionFromCatalog(string $version): ?string
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }

        try {
            $apiKey = config('app.flute_key');

            if (empty($apiKey)) {
                logs()->error('Flute API key is empty, cannot download version');

                return null;
            }

            $api = new FluteApiClient(timeout: 120, connectTimeout: 10);

            $downloadUrl = '/api/engine/download';

            $fullUrl = rtrim($api->getActiveBaseUrl(), '/') . $downloadUrl;

            $tempDir = storage_path('app/temp/updates');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0o755, true);
            }

            $safeVersion = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $version);
            $fileName = $tempDir . '/cms-' . $safeVersion . '.zip';

            $api->getClient()->request('GET', $fullUrl, [
                'headers' => [
                    'User-Agent' => 'Flute-CMS/' . App::VERSION,
                ],
                'sink' => $fileName,
                'query' => [
                    'version' => $version,
                    'accessKey' => $apiKey,
                ],
            ]);

            if (!file_exists($fileName) || mime_content_type($fileName) !== 'application/zip') {
                logs()->error("Downloaded catalog file is not a valid ZIP: {$fileName}");
                @unlink($fileName);

                return null;
            }

            return $fileName;
        } catch (\Throwable $e) {
            logs()->error('Failed to download version from catalog: ' . $e->getMessage());

            if (is_debug()) {
                throw $e;
            }
        }

        return null;
    }

    /**
     * Increment version number for mock data
     */
    protected function incrementVersion(string $version): string
    {
        $parts = explode('.', $version);
        $parts[count($parts) - 1]++;

        return implode('.', $parts);
    }

    /**
     * Fetch updates from external API
     */
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
                    'branch' => $this->channel,
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

    /**
     * Build mock updates dataset (for UI previews)
     */
    private function buildMockData(): array
    {
        $today = date(default_date_format(true));

        $cms = [
            'version' => $this->incrementVersion(App::VERSION),
            'release_date' => $today,
            'tags' => [
                ['type' => 'feature', 'label' => 'Features'],
                ['type' => 'security', 'label' => 'Security'],
            ],
            'changelog' => "# Highlights\n\n- New Dashboard widgets\n- Faster cache engine\n- Security patches\n\n## Details\n- Added support for Early channel\n- Improved UX for updates page",
            'previous_versions' => [
                [
                    'version' => $this->incrementVersion($this->incrementVersion(App::VERSION)),
                    'release_date' => $today,
                    'changelog' => "- Fix minor bugs\n- Improve performance",
                ],
            ],
        ];

        $modules = [
            'shop' => [
                'name' => 'Shop',
                'current_version' => '1.4.0',
                'version' => '1.5.0',
                'release_date' => $today,
                'changelog' => "- New coupons\n- Better analytics",
                'previous_versions' => [
                    ['version' => '1.4.5', 'release_date' => $today, 'changelog' => '- Hotfixes'],
                ],
            ],
            'rules' => [
                'name' => 'Rules',
                'current_version' => '2.0.0',
                'version' => '2.1.0',
                'release_date' => $today,
                'changelog' => "- Rich editor for rules\n- Export to PDF",
            ],
        ];

        $themes = [
            'standard' => [
                'name' => 'Standard Theme',
                'current_version' => '3.2.1',
                'version' => '3.3.0',
                'release_date' => $today,
                'changelog' => "- Polish profile card\n- New color tokens",
            ],
        ];

        return [
            'cms' => $cms,
            'modules' => $modules,
            'themes' => $themes,
        ];
    }

    /**
     * Parse Markdown changelogs in update data
     */
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

    /**
     * Get list of installed modules with their versions
     */
    private function getInstalledModules(): array
    {
        $modules = [];

        foreach ($this->moduleManager->getActive() as $module) {
            $updater = new ModuleUpdater($module);
            $modules[] = [
                'key' => $module->key,
                'version' => $updater->getCurrentVersion(),
            ];
        }

        return $modules;
    }

    /**
     * Get list of installed themes with their versions
     */
    private function getInstalledThemes(): array
    {
        $themes = [];

        foreach ($this->themeManager->getInstalledThemes() as $theme) {
            $themeData = $this->themeManager->getThemeData($theme->key);
            $updater = new ThemeUpdater($theme, $themeData);

            $themes[] = [
                'key' => $theme->key,
                'version' => $updater->getCurrentVersion(),
            ];
        }

        return $themes;
    }

    /**
     * Get PHP version
     */
    private function getPHPVersion(): string
    {
        return substr(PHP_VERSION, 0, 3);
    }
}
