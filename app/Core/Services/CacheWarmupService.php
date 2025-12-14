<?php

namespace Flute\Core\Services;

use Flute\Core\Database\DatabaseConnection;
use Flute\Core\ModulesManager\ModuleManager;
use Flute\Core\Router\Router;
use Flute\Core\Template\Template;
use GO\Scheduler;
use Throwable;

final class CacheWarmupService
{
    private const NEEDS_WARMUP_FILE = 'storage/app/cache_warmup_needed';

    private const WARMUP_LOCK_FILE = 'storage/app/cache_warmup.lock';

    public function setupCron(Scheduler $scheduler): void
    {
        if (!config('app.cron_mode')) {
            return;
        }

        $scheduler->call(function (): void {
            $this->warmupIfNeeded();
        })->everyMinute();
    }

    public function markNeeded(): void
    {
        $path = path(self::NEEDS_WARMUP_FILE);
        @mkdir(dirname($path), 0o755, true);
        @file_put_contents($path, (string) time(), LOCK_EX);
    }

    public function clearNeeded(): void
    {
        @unlink(path(self::NEEDS_WARMUP_FILE));
    }

    public function isNeeded(): bool
    {
        return is_file(path(self::NEEDS_WARMUP_FILE));
    }

    public function warmupIfNeeded(): void
    {
        if (!$this->isNeeded()) {
            return;
        }

        $this->warmup();
    }

    public function warmup(): void
    {
        $lockPath = path(self::WARMUP_LOCK_FILE);
        @mkdir(dirname($lockPath), 0o755, true);

        $handle = @fopen($lockPath, 'w+');
        if ($handle === false) {
            return;
        }

        if (!@flock($handle, LOCK_EX | LOCK_NB)) {
            @fclose($handle);

            return;
        }

        try {
            @ignore_user_abort(true);
            @set_time_limit(0);

            try {
                // Ensure modules caches are built.
                app(ModuleManager::class)->initialize();
            } catch (Throwable $e) {
                logs('cron')->warning($e);
            }

            try {
                // Ensure template is initialized (view/component caches).
                app(Template::class);
            } catch (Throwable $e) {
                logs('cron')->warning($e);
            }

            try {
                // Ensure compiled routes exist (front/admin).
                $router = app(Router::class);
                $router->warmupCompiledRoutes(false);
                $router->warmupCompiledRoutes(true);
            } catch (Throwable $e) {
                logs('cron')->warning($e);
            }

            try {
                // Rebuild ORM schema if it was rotated/staled or missing.
                $schemaFile = storage_path('app/orm_schema.php');
                $staleSchemaFile = storage_path('app/orm_schema.php.stale');
                if (!is_file($schemaFile) || is_file($staleSchemaFile)) {
                    app(DatabaseConnection::class)->forceRefreshSchema();
                }
            } catch (Throwable $e) {
                logs('cron')->warning($e);
            }

            $this->clearNeeded();
        } finally {
            @flock($handle, LOCK_UN);
            @fclose($handle);
        }
    }
}
