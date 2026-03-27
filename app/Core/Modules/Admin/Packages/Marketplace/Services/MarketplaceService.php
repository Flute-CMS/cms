<?php

namespace Flute\Admin\Packages\Marketplace\Services;

use Exception;
use Flute\Core\Services\FluteApiClient;

class MarketplaceService
{
    protected FluteApiClient $api;

    /**
     * Список модулей в кеше
     */
    protected ?array $cachedModules = null;

    /**
     * Время кеширования модулей (в секундах)
     */
    protected int $cacheTime = 3600; // 1 hour

    public function __construct()
    {
        $this->api = new FluteApiClient(timeout: 10, connectTimeout: 5);
    }

    /**
     * Получить список модулей
     *
     * @throws Exception
     */
    public function getModules(string $searchQuery = '', string $category = '', bool $force = false): array
    {
        $cacheKey = 'marketplace_modules_' . md5($searchQuery . '_' . $category);

        if ($force) {
            cache()->delete($cacheKey);
        }

        return cache()->callback(
            $cacheKey,
            function () use ($searchQuery, $category) {
                $queryParams = [
                    'accessKey' => $this->api->getApiKey(),
                    'php' => $this->getPHPVersion(),
                ];

                if (!empty($searchQuery)) {
                    $queryParams['search'] = $searchQuery;
                }

                if (!empty($category)) {
                    $queryParams['category'] = $category;
                }

                try {
                    $modules = $this->api->getJson('/api/external/modules', $queryParams);

                    $this->updateModuleCacheKeys('marketplace_modules_' . md5($searchQuery . '_' . $category));

                    return $modules;
                } catch (\Throwable $e) {
                    logs()->error('Marketplace API error: ' . $e->getMessage());

                    throw new Exception(__('admin-marketplace.api_error', $e->getMessage()));
                }
            },
            $this->cacheTime,
        );
    }

    /**
     * Get module information by slug
     *
     * @throws Exception
     */
    public function getModuleBySlug(string $slug): array
    {
        $cacheKey = 'marketplace_module_' . $slug;

        return cache()->callback(
            $cacheKey,
            function () use ($slug) {
                try {
                    $module = $this->api->getJson("/api/external/modules/{$slug}", [
                        'accessKey' => $this->api->getApiKey(),
                        'php' => $this->getPHPVersion(),
                    ]);

                    $this->updateModuleCacheKeys('marketplace_module_' . $slug);

                    return $module;
                } catch (\Throwable $e) {
                    logs()->error('Marketplace API error: ' . $e->getMessage());

                    throw new Exception(__('admin-marketplace.api_error', $e->getMessage()));
                }
            },
            $this->cacheTime,
        );
    }

    /**
     * Get module version history
     *
     * @throws Exception
     */
    public function getModuleVersions(string $slug): array
    {
        $cacheKey = 'marketplace_module_versions_' . $slug;

        return cache()->callback(
            $cacheKey,
            function () use ($slug) {
                try {
                    $versions = $this->api->getJson("/api/external/modules/{$slug}/versions", [
                        'accessKey' => $this->api->getApiKey(),
                    ]);

                    $this->updateModuleCacheKeys('marketplace_module_versions_' . $slug);

                    return $versions;
                } catch (\Throwable $e) {
                    logs()->error('Marketplace API error: ' . $e->getMessage());

                    throw new Exception(__('admin-marketplace.api_error', $e->getMessage()));
                }
            },
            $this->cacheTime,
        );
    }

    /**
     * Get module categories (filters)
     *
     * @throws Exception
     */
    public function getCategories(): array
    {
        $cacheKey = 'marketplace_categories';

        return cache()->callback(
            $cacheKey,
            function () {
                try {
                    $data = $this->api->getJson('/api/external/market/filters', [
                        'accessKey' => $this->api->getApiKey(),
                    ]);

                    return $data['tags'] ?? [];
                } catch (\Throwable $e) {
                    logs()->error('Marketplace API error: ' . $e->getMessage());

                    throw new Exception(__('admin-marketplace.api_error', $e->getMessage()));
                }
            },
            $this->cacheTime,
        );
    }

    /**
     * Download module
     *
     * @throws Exception
     * @return string Path to the downloaded file
     */
    public function downloadModule(string $slug): string
    {
        try {
            $module = $this->getModuleBySlug($slug);

            if (empty($module['downloadUrl'])) {
                throw new Exception('Download link for the module not found');
            }

            $downloadUrl = $module['downloadUrl'];

            // Relative URL — prepend active mirror base.
            if (!str_starts_with($downloadUrl, 'http')) {
                $downloadUrl = rtrim($this->api->getActiveBaseUrl(), '/') . '/' . ltrim($downloadUrl, '/');
            }

            $response = $this->api->getClient()->get($downloadUrl, [
                'sink' => storage_path('app/temp/modules/' . $slug . '.zip'),
                'timeout' => 60,
                'connect_timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                throw new Exception('Error downloading module');
            }

            return storage_path('app/temp/modules/' . $slug . '.zip');
        } catch (\Throwable $e) {
            logs()->error('Marketplace download error: ' . $e->getMessage());

            throw new Exception(__('admin-marketplace.api_error', $e->getMessage()));
        }
    }

    /**
     * Clear all marketplace cache
     */
    public function clearCache(): void
    {
        cache()->delete('marketplace_modules');
        cache()->delete('marketplace_categories');

        $cacheKeys = cache()->get('marketplace_module_caches', []);
        foreach ($cacheKeys as $key) {
            cache()->delete($key);
        }

        cache()->delete('marketplace_module_caches');
    }

    /**
     * Clear cache for a specific module
     */
    public function clearModuleCache(string $slug): void
    {
        cache()->delete('marketplace_module_' . $slug);
        cache()->delete('marketplace_module_versions_' . $slug);

        $cacheKeys = cache()->get('marketplace_module_caches', []);
        foreach ($cacheKeys as $key) {
            if (str_starts_with($key, 'marketplace_modules_')) {
                cache()->delete($key);
            }
        }
    }

    protected function updateModuleCacheKeys(string $cacheKey): void
    {
        $cacheKeys = cache()->get('marketplace_module_caches', []);
        if (!in_array($cacheKey, $cacheKeys)) {
            $cacheKeys[] = $cacheKey;
            cache()->set('marketplace_module_caches', $cacheKeys, $this->cacheTime * 2);
        }
    }

    private function getPHPVersion(): string
    {
        return substr(PHP_VERSION, 0, 3);
    }
}
