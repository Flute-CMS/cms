<?php

namespace Flute\Admin\Packages\Marketplace\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class MarketplaceService
{
    /**
     * API базовый URL
     * 
     * @var string
     */
    protected string $apiBaseUrl;

    /**
     * API ключ
     * 
     * @var string
     */
    protected string $apiKey;

    /**
     * HTTP клиент
     * 
     * @var Client
     */
    protected Client $client;

    /**
     * API URL маркетплейса
     * 
     * @var string
     */
    protected string $apiUrl;

    /**
     * Список модулей в кеше
     * 
     * @var array|null
     */
    protected ?array $cachedModules = null;

    /**
     * Время кеширования модулей (в секундах)
     * 
     * @var int
     */
    protected int $cacheTime = 3600; // 1 hour

    /**
     * MarketplaceService constructor.
     */
    public function __construct()
    {
        $this->apiBaseUrl = config('app.flute_market_url', 'https://flute-cms.com/api');
        $this->apiKey = config('app.flute_key', '');

        $this->client = new Client([
            'base_uri' => $this->apiBaseUrl,
            'timeout' => 10,
            'http_errors' => false,
        ]);
    }

    /**
     * Получить список модулей
     * 
     * @param string $searchQuery Строка поиска
     * @param string $category Категория модулей
     * @param bool $force Принудительное обновление кеша
     * @return array
     * @throws Exception
     */
    public function getModules(string $searchQuery = '', string $category = '', bool $force = false): array
    {
        $cacheKey = 'marketplace_modules_' . md5($searchQuery . '_' . $category);

        if ($force) {
            cache()->delete($cacheKey);
        }

        return cache()->callback($cacheKey, function () use ($searchQuery, $category) {
            $queryParams = [
                'accessKey' => $this->apiKey,
                'php' => $this->getPHPVersion(),
            ];

            if (!empty($searchQuery)) {
                $queryParams['search'] = $searchQuery;
            }

            if (!empty($category)) {
                $queryParams['category'] = $category;
            }

            try {
                $response = $this->client->get('/api/external/modules', [
                    'query' => $queryParams,
                ]);

                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();

                if ($statusCode !== 200) {
                    $error = json_decode($body, true);
                    throw new \Exception($error['error'] ?? 'Ошибка при получении модулей');
                }

                $modules = json_decode($body, true) ?? [];
                
                // Сохраняем ключи модулей в кеше для последующей очистки
                $this->updateModuleCacheKeys('marketplace_modules_' . md5($searchQuery . '_' . $category));
                
                return $modules;
            } catch (GuzzleException $e) {
                logs()->error('Marketplace API error: ' . $e->getMessage());
                throw new \Exception('Ошибка соединения с API маркетплейса: ' . $e->getMessage());
            }
        }, $this->cacheTime);
    }

    /**
     * Сохранить ключи кеша модулей для последующей очистки
     * 
     * @param string $cacheKey
     * @return void
     */
    protected function updateModuleCacheKeys(string $cacheKey): void
    {
        $cacheKeys = cache()->get('marketplace_module_caches', []);
        if (!in_array($cacheKey, $cacheKeys)) {
            $cacheKeys[] = $cacheKey;
            cache()->set('marketplace_module_caches', $cacheKeys, $this->cacheTime * 2);
        }
    }

    /**
     * Получить информацию о модуле по slug
     * 
     * @param string $slug
     * @return array
     * @throws Exception
     */
    public function getModuleBySlug(string $slug): array
    {
        $cacheKey = 'marketplace_module_' . $slug;

        return cache()->callback($cacheKey, function () use ($slug) {
            try {
                $response = $this->client->get("/api/external/modules/{$slug}", [
                    'query' => [
                        'accessKey' => $this->apiKey,
                        'php' => $this->getPHPVersion(),
                    ],
                ]);

                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();

                if ($statusCode !== 200) {
                    $error = json_decode($body, true);
                    throw new \Exception($error['error'] ?? 'Модуль не найден');
                }

                $module = json_decode($body, true) ?? [];
                
                $this->updateModuleCacheKeys('marketplace_module_' . $slug);
                
                return $module;
            } catch (GuzzleException $e) {
                logs()->error('Marketplace API error: ' . $e->getMessage());
                throw new \Exception('Ошибка соединения с API маркетплейса: ' . $e->getMessage());
            }
        }, $this->cacheTime);
    }

    /**
     * Получить историю версий модуля
     * 
     * @param string $slug
     * @return array
     * @throws Exception
     */
    public function getModuleVersions(string $slug): array
    {
        $cacheKey = 'marketplace_module_versions_' . $slug;

        return cache()->callback($cacheKey, function () use ($slug) {
            try {
                $response = $this->client->get("/api/external/modules/{$slug}/versions", [
                    'query' => [
                        'accessKey' => $this->apiKey,
                    ],
                ]);

                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();

                if ($statusCode !== 200) {
                    $error = json_decode($body, true);
                    throw new \Exception($error['error'] ?? 'Не удалось получить список версий модуля');
                }

                $versions = json_decode($body, true) ?? [];
                
                // Сохраняем ключ версий в кеше для последующей очистки
                $this->updateModuleCacheKeys('marketplace_module_versions_' . $slug);
                
                return $versions;
            } catch (GuzzleException $e) {
                logs()->error('Marketplace API error: ' . $e->getMessage());
                throw new \Exception('Ошибка соединения с API маркетплейса: ' . $e->getMessage());
            }
        }, $this->cacheTime);
    }

    /**
     * Получить категории модулей (фильтры)
     * 
     * @return array
     * @throws Exception
     */
    public function getCategories(): array
    {
        $cacheKey = 'marketplace_categories';

        return cache()->callback($cacheKey, function () {
            try {
                $response = $this->client->get('/api/external/market/filters', [
                    'query' => [
                        'accessKey' => $this->apiKey,
                    ],
                ]);

                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();

                if ($statusCode !== 200) {
                    $error = json_decode($body, true);
                    throw new \Exception($error['error'] ?? 'Ошибка при получении категорий');
                }

                $data = json_decode($body, true) ?? [];
                return $data['tags'] ?? [];
            } catch (GuzzleException $e) {
                logs()->error('Marketplace API error: ' . $e->getMessage());
                throw new \Exception('Ошибка соединения с API маркетплейса: ' . $e->getMessage());
            }
        }, $this->cacheTime);
    }

    /**
     * Скачать модуль
     * 
     * @param string $slug
     * @return string Путь к скачанному файлу
     * @throws Exception
     */
    public function downloadModule(string $slug): string
    {
        try {
            $module = $this->getModuleBySlug($slug);

            if (empty($module['downloadUrl'])) {
                throw new \Exception('Ссылка для скачивания модуля не найдена');
            }

            $response = $this->client->get($module['downloadUrl'], [
                'sink' => storage_path('app/temp/modules/' . $slug . '.zip'),
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                throw new \Exception('Ошибка при скачивании модуля');
            }

            return storage_path('app/temp/modules/' . $slug . '.zip');
        } catch (GuzzleException $e) {
            logs()->error('Marketplace API error: ' . $e->getMessage());
            throw new \Exception('Ошибка соединения с API маркетплейса: ' . $e->getMessage());
        }
    }

    /**
     * Получить версию PHP
     * 
     * @return string
     */
    private function getPHPVersion(): string
    {
        return substr(PHP_VERSION, 0, 3);
    }

    /**
     * Очистить кеш
     * 
     * @return void
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
}
