<?php

namespace Flute\Core\SystemHealth;

use Flute\Core\SystemHealth\Migrations\CheckPermissionsMigration;

/**
 * This class checking system values and migrates them if it needed.
 */
class SystemHealthCheck
{
    protected const SYSTEM_CACHE_KEY = 'flute.system_health';

    /**
     * Bump this version whenever system migrations change (e.g. new permissions added).
     * This invalidates the cache and forces re-run on next request.
     */
    protected const SYSTEM_VERSION = 2;

    protected array $systemServices = [
        CheckPermissionsMigration::class,
    ];

    public function run(bool $force = false): void
    {
        if (!$this->isAllowed() && !$force) {
            return;
        }

        foreach ($this->systemServices as $service) {
            if (is_callable([$service, 'run'])) {
                app($service)->run();
            }
        }

        $this->updateSystemCacheKey();
    }

    /**
     * Check if system health check is allowed by cache
     */
    protected function isAllowed(): bool
    {
        return cache()->get(self::SYSTEM_CACHE_KEY) !== self::SYSTEM_VERSION;
    }

    /**
     * Updates the allowed cache key
     */
    protected function updateSystemCacheKey(): void
    {
        cache()->set(self::SYSTEM_CACHE_KEY, self::SYSTEM_VERSION, 3600);
    }
}
