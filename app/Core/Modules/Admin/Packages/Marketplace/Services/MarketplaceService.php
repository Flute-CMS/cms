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
            return $this->fetchModules($cacheKey, $searchQuery, $category);
        }

        return cache()->callback(
            $cacheKey,
            fn() => $this->fetchModules($cacheKey, $searchQuery, $category),
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
            fn() => $this->fetchModuleBySlug($cacheKey, $slug),
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
            fn() => $this->fetchModuleVersions($cacheKey, $slug),
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
            fn() => $this->fetchCategories($cacheKey),
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

    private function fetchModules(string $cacheKey, string $searchQuery = '', string $category = ''): array
    {
        $queryParams = [
            'accessKey' => $this->api->getApiKey(),
            'php' => $this->getPHPVersion(),
        ];

        if ($searchQuery !== '') {
            $queryParams['search'] = $searchQuery;
        }

        if ($category !== '') {
            $queryParams['category'] = $category;
        }

        return $this->fetchOrFallback(
            $cacheKey,
            function () use ($queryParams, $cacheKey) {
                $modules = $this->api->getJson('/api/external/modules', $queryParams);
                $this->updateModuleCacheKeys($cacheKey);

                return $modules;
            },
            [],
        );
    }

    private function fetchModuleBySlug(string $cacheKey, string $slug): array
    {
        return $this->fetchOrFallback(
            $cacheKey,
            function () use ($slug, $cacheKey) {
                $module = $this->api->getJson("/api/external/modules/{$slug}", [
                    'accessKey' => $this->api->getApiKey(),
                    'php' => $this->getPHPVersion(),
                ]);

                $this->updateModuleCacheKeys($cacheKey);

                return $module;
            },
            $this->findModuleInCachedLists($slug),
        );
    }

    private function fetchModuleVersions(string $cacheKey, string $slug): array
    {
        return $this->fetchOrFallback(
            $cacheKey,
            fn() => $this->api->getJson("/api/external/modules/{$slug}/versions", [
                'accessKey' => $this->api->getApiKey(),
            ]),
            [],
        );
    }

    private function fetchCategories(string $cacheKey): array
    {
        return $this->fetchOrFallback(
            $cacheKey,
            function () {
                $data = $this->api->getJson('/api/external/market/filters', [
                    'accessKey' => $this->api->getApiKey(),
                ]);

                return $data['tags'] ?? [];
            },
            [],
        );
    }

    private function fetchOrFallback(string $cacheKey, callable $callback, array $default = []): array
    {
        try {
            $result = $callback();
            cache()->set($cacheKey, $result, $this->cacheTime);

            return is_array($result) ? $result : $default;
        } catch (\Throwable $e) {
            logs()->warning('Marketplace API error: ' . $e->getMessage(), ['cache_key' => $cacheKey]);

            $cached = cache()->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }

            return $default;
        }
    }

    private function findModuleInCachedLists(string $slug): array
    {
        $cacheKeys = cache()->get('marketplace_module_caches', []);

        if (!is_array($cacheKeys)) {
            return [];
        }

        foreach ($cacheKeys as $key) {
            if (!is_string($key) || !str_starts_with($key, 'marketplace_modules_')) {
                continue;
            }

            $modules = cache()->get($key, []);
            if (!is_array($modules)) {
                continue;
            }

            foreach ($modules as $module) {
                if (is_array($module) && ($module['slug'] ?? null) === $slug) {
                    return $module;
                }
            }
        }

        return [];
    }
}
