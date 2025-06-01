<?php

namespace Flute\Core\SystemHealth;

use Flute\Core\SystemHealth\Migrations\CheckPermissionsMigration;

/**
 * This class checking system values and migrates them if it needed.
 */
class SystemHealthCheck
{
    protected const SYSTEM_CACHE_KEY = 'flute.system_health';
    protected array $systemServices = [
        CheckPermissionsMigration::class
    ];

    public function run(bool $force = false): void
    {
        if (!$this->isAllowed() && !$force)
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