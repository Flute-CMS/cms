<?php

namespace Flute\Core\ModulesManager;

use Cycle\ORM\Exception\ORMException;
use DI\DependencyException;
use DI\NotFoundException;
use Flute\Core\Support\ModuleServiceProvider;

class ModuleRegister
{
    /** @var array Времена загрузки модулей */
    protected static array $modulesBootTimes = [];

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
                    logs('modules')->error("Module $classPath wasn't found in the FileSystem!");

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
            } catch (\Exception $e) {
                logs('modules')->error($e);

                // Schema exception is not critical and can be ignored
                if (user()->can('admin.boss') && !($e instanceof ORMException)) {
                    throw $e;
                }
            }
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

    /**
     * Get modules boot times
     *
     * @return array
     */
    public static function getModulesBootTimes(): array
    {
        return self::$modulesBootTimes;
    }
}
