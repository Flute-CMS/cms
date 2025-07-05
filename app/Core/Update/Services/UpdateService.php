<?php

namespace Flute\Core\Update\Services;

use Flute\Core\App;
use Flute\Core\ModulesManager\ModuleManager;
use Flute\Core\Theme\ThemeManager;
use Flute\Core\Update\Updaters\CmsUpdater;
use Flute\Core\Update\Updaters\ModuleUpdater;
use Flute\Core\Update\Updaters\ThemeUpdater;
use Flute\Core\Markdown\Parser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

use function random_int;

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
     * Update API URL
     */
    private const UPDATE_API_URL = 'https://flute-cms.com/api';

    /**
     * Local API Update
     */
    // private const LOCAL_API_UPDATE_URL = 'http://localhost:3000/api';
    private const LOCAL_API_UPDATE_URL = 'https://flute-cms.com/api';

    /**
     * @var ModuleManager
     */
    protected ModuleManager $moduleManager;

    /**
     * @var ThemeManager
     */
    protected ThemeManager $themeManager;

    /**
     * @var bool
     */
    protected bool $useMockData = false;

    /**
     * @var Parser
     */
    protected Parser $markdownParser;

    /**
     * UpdateService constructor.
     */
    public function __construct(ModuleManager $moduleManager, ThemeManager $themeManager, Parser $markdownParser = null)
    {
        $this->moduleManager = $moduleManager;
        $this->themeManager = $themeManager;
        $this->markdownParser = $markdownParser ?? new Parser();

        // For local development and testing
        // if (config('app.debug') && empty(config('app.flute_key'))) {
        //     $this->useMockData = true;
        // }
    }

    /**
     * Get available updates for all components
     *
     * @param bool $forceRefresh Принудительно обновить кэш
     * @return array
     */
    public function getAvailableUpdates(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            cache()->delete(self::CACHE_KEY);
            return $this->fetchUpdatesFromApi();
        }
        
        return cache()->callback(self::CACHE_KEY, function () {
            return $this->fetchUpdatesFromApi();
        }, self::CACHE_DURATION);
    }

    /**
     * Check if updates are available for a specific component
     *
     * @param string $type cms|module|theme
     * @param string|null $identifier Component identifier
     * @return bool
     */
    public function hasUpdate(string $type, ?string $identifier = null): bool
    {
        $updates = $this->getAvailableUpdates();

        if ($type === 'cms') {
            return ! empty($updates['cms']);
        }

        return ! empty($updates[$type . 's'][$identifier] ?? null);
    }

    /**
     * Get update details for a specific component
     *
     * @param string $type cms|module|theme
     * @param string|null $identifier Component identifier
     * @return array|null
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
     * Increment version number for mock data
     *
     * @param string $version
     * @return string
     */
    protected function incrementVersion(string $version): string
    {
        $parts = explode('.', $version);
        $parts[count($parts) - 1]++;
        return implode('.', $parts);
    }

    /**
     * Fetch updates from external API
     *
     * @return array
     */
    private function fetchUpdatesFromApi(): array
    {
        try {
            $client = new Client(['timeout' => 10, 'verify' => !config('app.debug')]);
            $apiKey = config('app.flute_key');

            if (empty($apiKey)) {
                logs()->warning('Flute API key is empty. Can\'t fetch updates.');
                return [];
            }

            $url = (str_contains(config('app.url'), 'localhost') ? self::LOCAL_API_UPDATE_URL : self::UPDATE_API_URL) . '/updates';

            $response = $client->request('GET', $url, [
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
                    'nocache' => time(),
                ]
            ]);


            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);

                if (is_array($data)) {
                    $data = $this->parseMarkdownChangelogs($data);
                    return $data;
                }
            }
        } catch (GuzzleException $e) {
            // if (is_debug()) {
            //     throw $e;
            // }

            logs()->error('Failed to fetch updates: ' . $e->getMessage());
        } catch (\Exception $e) {
            // if (is_debug()) {
            //     throw $e;
            // }

            logs()->error('Error processing updates: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Parse Markdown changelogs in update data
     *
     * @param array $data
     * @return array
     */
    private function parseMarkdownChangelogs(array $data): array
    {
        if (!empty($data['cms']) && is_array($data['cms'])) {
            if (!empty($data['cms']['changelog'])) {
                $data['cms']['changelog_html'] = $this->markdownParser->parse($data['cms']['changelog']);
            }

            if (!empty($data['cms']['previous_versions'])) {
                foreach ($data['cms']['previous_versions'] as $key => $version) {
                    if (!empty($version['changelog'])) {
                        $data['cms']['previous_versions'][$key]['changelog_html'] =
                            $this->markdownParser->parse($version['changelog']);
                    }
                }
            }
        }

        if (!empty($data['modules']) && is_array($data['modules'])) {
            foreach ($data['modules'] as $moduleId => $module) {
                if (!empty($module['changelog']) && empty($module['changelog_html'])) {
                    $data['modules'][$moduleId]['changelog_html'] =
                        $this->markdownParser->parse($module['changelog']);
                }

                if (!empty($module['previous_versions'])) {
                    foreach ($module['previous_versions'] as $vKey => $version) {
                        if (!empty($version['changelog']) && empty($version['changelog_html'])) {
                            $data['modules'][$moduleId]['previous_versions'][$vKey]['changelog_html'] =
                                $this->markdownParser->parse($version['changelog']);
                        }
                    }
                }
            }
        }

        if (!empty($data['themes']) && is_array($data['themes'])) {
            foreach ($data['themes'] as $themeId => $theme) {
                if (!empty($theme['changelog']) && empty($theme['changelog_html'])) {
                    $data['themes'][$themeId]['changelog_html'] =
                        $this->markdownParser->parse($theme['changelog']);
                }

                if (!empty($theme['previous_versions'])) {
                    foreach ($theme['previous_versions'] as $vKey => $version) {
                        if (!empty($version['changelog']) && empty($version['changelog_html'])) {
                            $data['themes'][$themeId]['previous_versions'][$vKey]['changelog_html'] =
                                $this->markdownParser->parse($version['changelog']);
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Get list of installed modules with their versions
     *
     * @return array
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
     *
     * @return array
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
     * Clear update cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        cache()->delete(self::CACHE_KEY);
        
        $this->getAvailableUpdates(true);
        
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * Download update package
     *
     * @param string $type
     * @param string|null $identifier
     * @param string|null $version
     * @return string|null Path to downloaded file or null on failure
     */
    public function downloadUpdate(string $type, ?string $identifier = null, ?string $version = null): ?string
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
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
                logs()->error("Download URL not found for {$type} " . ($identifier ?? ''));
                return null;
            }

            $tempDir = storage_path('app/temp/updates');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $fileName = $tempDir . '/' . ($identifier ?? 'cms') . '-' . ($version ?? $latestVersion) . '.zip';

            $client = new Client(['timeout' => 30, 'verify' => !config('app.debug')]);

            $baseUrl = '';
            if (!preg_match('/^https?:\/\//', $downloadUrl)) {
                $baseUrl = (str_contains(config('app.url'), 'localhost') ? self::LOCAL_API_UPDATE_URL : self::UPDATE_API_URL);
            }

            // parse ?token
            $token = explode('?', $downloadUrl)[1];
            $token = explode('=', $token)[1];

            $client->request('GET', $baseUrl . str_replace('api/', '', $downloadUrl), [
                'headers' => [
                    'User-Agent' => 'Flute-CMS/' . App::VERSION,
                ],
                'sink' => $fileName,
                'query' => [
                    'accessKey' => config('app.flute_key'),
                    'versionId' => $version ?? $latestVersion,
                    'token' => $token,
                ]
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
        } catch (\Exception $e) {
            logs()->error('Error processing update download: ' . $e->getMessage());

            if (is_debug()) {
                throw $e;
            }
        }

        return null;
    }

    /**
     * Get PHP version
     * 
     * @return string
     */
    private function getPHPVersion(): string
    {
        return substr(PHP_VERSION, 0, 3);
    }
}
