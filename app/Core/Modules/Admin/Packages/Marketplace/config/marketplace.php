<?php

return [
    /**
     * URL API маркетплейса
     */
    'api_url' => env('MARKETPLACE_API_URL', 'https://flute-cms.com/api'),

    /**
     * Ключ доступа к API
     */
    'api_key' => env('MARKETPLACE_API_KEY', ''),

    /**
     * Время жизни кеша в секундах
     */
    'cache_ttl' => env('MARKETPLACE_CACHE_TTL', 3600),

    /**
     * Временная директория для загрузки модулей
     */
    'temp_dir' => storage_path('app/temp/marketplace'),
];
