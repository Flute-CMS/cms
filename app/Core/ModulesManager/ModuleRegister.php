<?php

namespace Flute\Core\ModulesManager;

use Cycle\ORM\Exception\ORMException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Support\ModuleServiceProvider;
use Throwable;

class ModuleRegister
{
    protected const STATS_CACHE_KEY = 'modules.boot_times_stats';

    protected const STATS_MAX_SAMPLES = 100;

    /** @var array Времена загрузки модулей */
    protected static array $modulesBootTimes = [];

    /** @var bool Флаг что статистика уже сохранена */
    protected static bool $statsSaved = false;

    /**
     * Register service providers
     *
     * @param array $providers List of service providers to register.
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function registerServiceProviders(array $providers)
    {
        foreach ($providers as $provider) {
            try {
                $classPath = self::normalizeClassPath($provider['class']);

                if (!class_exists(self::normalizeClassPath($classPath))) {
                    logs('modules')->error("Module {$classPath} wasn't found in the FileSystem!");

                    continue;
                }

                $module = self::instantiateClass($classPath);

                /** @var ModuleServiceProvider */
                $class = $module instanceof ModuleServiceProvider ? $module : app()->get($module);

                $class->setModuleName($provider['module']);

                $container = app()->getContainer();

                $startRegisterTime = microtime(true);
                $class->register($container);
                $registerTime = microtime(true) - $startRegisterTime;

                if ($provider['active']) {
                    $startBootTime = microtime(true);
                    $class->boot($container);
                    $bootTime = microtime(true) - $startBootTime;

                    $totalTime = $registerTime + $bootTime;
                    self::$modulesBootTimes[$provider['module']] = round($totalTime, 3);

                    if ($class->isExtensionsCallable() && !empty($class->extensions)) {
                        $extensionsStartTime = microtime(true);

                        foreach ($class->extensions ?? [] as $extension) {
                            $extension = self::instantiateClass($extension);
                            $extension->register();
                        }

                        $extensionsTime = microtime(true) - $extensionsStartTime;

                        self::$modulesBootTimes[$provider['module']] += round($extensionsTime, 3);
                    }
                }
            } catch (Exception $e) {
                logs('modules')->error($e);

                // In production mode errors are often hidden; if a module fails because an ORM schema
                // is missing, schedule a schema refresh so modules/widgets/routes recover on next request.
                if ($e instanceof ORMException && str_contains($e->getMessage(), 'Undefined schema')) {
                    try {
                        app(DatabaseConnection::class)->forceRefreshSchemaDeferred([$provider['module'] ?? null]);
                    } catch (Throwable) {
                    }
                }

                // Schema exception is not critical and can be ignored
                if (user()->can('admin.boss') && !($e instanceof ORMException)) {
                    throw $e;
                }
            }
        }

        self::saveBootTimesStats();
    }

    /**
     * Get modules boot times
     */
    public static function getModulesBootTimes(): array
    {
        return self::$modulesBootTimes;
    }

    /**
     * Get accumulated boot times statistics
     */
    public static function getBootTimesStats(): array
    {
        try {
            if (!function_exists('cache')) {
                return [];
            }

            return cache()->get(self::STATS_CACHE_KEY, []);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Clear boot times statistics
     */
    public static function clearBootTimesStats(): void
    {
        try {
            if (function_exists('cache')) {
                cache()->delete(self::STATS_CACHE_KEY);
            }
        } catch (Throwable $e) {
        }
    }

    /**
     * Save boot times to statistics cache
     */
    protected static function saveBootTimesStats(): void
    {
        if (self::$statsSaved || empty(self::$modulesBootTimes)) {
            return;
        }

        self::$statsSaved = true;

        try {
            if (!function_exists('cache')) {
                return;
            }

            $stats = cache()->get(self::STATS_CACHE_KEY, [
                'samples' => [],
                'modules' => [],
                'last_updated' => null,
            ]);

            $timestamp = time();
            $stats['samples'][] = [
                'time' => $timestamp,
                'data' => self::$modulesBootTimes,
                'total' => array_sum(self::$modulesBootTimes),
            ];

            if (count($stats['samples']) > self::STATS_MAX_SAMPLES) {
                $stats['samples'] = array_slice($stats['samples'], -self::STATS_MAX_SAMPLES);
            }

            foreach (self::$modulesBootTimes as $module => $time) {
                if (!isset($stats['modules'][$module])) {
                    $stats['modules'][$module] = [];
                }
                $stats['modules'][$module][] = $time;

                if (count($stats['modules'][$module]) > self::STATS_MAX_SAMPLES) {
                    $stats['modules'][$module] = array_slice($stats['modules'][$module], -self::STATS_MAX_SAMPLES);
                }
            }

            $stats['last_updated'] = $timestamp;

            cache()->set(self::STATS_CACHE_KEY, $stats, 86400 * 7);
        } catch (Throwable $e) {
        }
    }

    /**
     * Create an instance of a class by its path.
     *
     * @param string $classPath Path to the class.
     * @return object New instance of the class.
     */
    protected static function instantiateClass(string $classPath): object
    {
        $path = self::normalizeClassPath($classPath);

        return new $path();
    }

    /**
     * Normalize the path to the class, replacing '/' with '\'.
     *
     * @param string $class Original path to the class.
     * @return string Normalized path to the class.
     */
    protected static function normalizeClassPath(string $class): string
    {
        return str_replace('/', '\\', $class);
    }
}
