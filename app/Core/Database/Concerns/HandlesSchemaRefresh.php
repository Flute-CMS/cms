<?php

namespace Flute\Core\Database\Concerns;

use Flute\Core\Cache\SWRQueue;
use Flute\Core\Database\Entities\Module;
use Throwable;

trait HandlesSchemaRefresh
{
    public function forceRefreshSchema(array $extraModules = []): void
    {
        logs()->info('Force refreshing ORM schema');

        if (file_exists(self::SCHEMA_FILE)) {
            $stale = self::SCHEMA_FILE . '.stale';
            @unlink($stale);
            if (!@rename(self::SCHEMA_FILE, $stale)) {
                @unlink(self::SCHEMA_FILE);
            }
        }

        if (file_exists(self::SCHEMA_META_FILE)) {
            $stale = self::SCHEMA_META_FILE . '.stale';
            @unlink($stale);
            if (!@rename(self::SCHEMA_META_FILE, $stale)) {
                @unlink(self::SCHEMA_META_FILE);
            }
        }

        $schemaFpCache =
            BASE_PATH
            . 'storage'
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'cache'
            . DIRECTORY_SEPARATOR
            . 'schema_fp_cache.php';
        @unlink($schemaFpCache);

        $this->entitiesDirs = [self::ENTITIES_DIR];

        $moduleKeys = [];

        try {
            $cached = cache()->get('flute.modules.alldb', []);
            if (is_array($cached)) {
                foreach ($cached as $row) {
                    $key = $row['key'] ?? null;
                    $status = $row['status'] ?? null;
                    if (is_string($key) && $key !== '' && $status !== 'notinstalled') {
                        $moduleKeys[$key] = true;
                    }
                }
            }
        } catch (Throwable) {
        }

        if (empty($moduleKeys)) {
            try {
                $modules = Module::findAll();
                foreach ($modules as $module) {
                    if ($module->status !== 'notinstalled') {
                        $moduleKeys[$module->key] = true;
                    }
                }
            } catch (Throwable) {
            }
        }

        foreach ($extraModules as $k) {
            if (is_string($k) && $k !== '') {
                $moduleKeys[$k] = true;
            }
        }

        foreach (array_keys($moduleKeys) as $moduleKey) {
            $candidates = [
                path("app/Modules/{$moduleKey}/database/Entities"),
                path("app/Modules/{$moduleKey}/Database/Entities"),
            ];

            foreach ($candidates as $entitiesDir) {
                if (is_dir($entitiesDir)) {
                    $this->entitiesDirs[] = $entitiesDir;

                    break;
                }
            }
        }

        $this->recompileOrmSchema(false);

        logs()->info('ORM schema refreshed successfully');
    }

    public function forceRefreshSchemaDeferred(array $extraModules = []): void
    {
        if (function_exists('is_cli') && is_cli()) {
            $this->forceRefreshSchema($extraModules);

            return;
        }

        if (function_exists('cache_warmup_mark')) {
            cache_warmup_mark();
        }

        foreach ($extraModules as $k) {
            if (is_string($k) && $k !== '') {
                self::$schemaRefreshExtraModules[$k] = true;
            }
        }

        if (self::$schemaRefreshQueued) {
            return;
        }

        self::$schemaRefreshQueued = true;

        SWRQueue::queue('database.force_refresh_schema', function (): void {
            $modules = array_keys(self::$schemaRefreshExtraModules);

            self::$schemaRefreshExtraModules = [];
            self::$schemaRefreshQueued = false;

            $this->forceRefreshSchema($modules);
        });
    }

    private function queueDeferredSchemaRefresh(): void
    {
        if (self::$schemaRefreshQueued) {
            return;
        }

        $this->forceRefreshSchemaDeferred();
    }
}
