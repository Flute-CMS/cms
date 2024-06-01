<?php

namespace Flute\Core\Services\System;

use Flute\Core\Services\System\Migrations\CheckPermissionsMigration;

/**
 * This class checking system values and migrates them if it needed.
 */
class SystemService
{
    protected const SYSTEM_CACHE_KEY = 'flute.system_health';
    protected array $systemServices = [
        CheckPermissionsMigration::class
    ];

    public function run(): void
    {
        if (!$this->isAllowed())
            return;

        foreach ($this->systemServices as $service) {
            if (is_callable([$service, 'run'])) {
                app($service)->run();
            }
        }

        $this->updateSystemCacheKey();
    }

    /**
     * Check if system health check is allowed by cache
     * 
     * @return bool
     */
    protected function isAllowed(): bool
    {
        return !cache()->has(self::SYSTEM_CACHE_KEY);
    }

    /**
     * Updates the allowed cache key
     * 
     * @return void
     */
    protected function updateSystemCacheKey(): void
    {
        cache()->set(self::SYSTEM_CACHE_KEY, 1, 3600);
    }
}