<?php

namespace Flute\Core\Modules;

use DI\DependencyException;
use DI\NotFoundException;
use Flute\Core\Support\ModuleServiceProvider;

/**
 * Class ModuleRegister
 * 
 * Класс для регистрации поставщиков услуг в приложении.
 */
class ModuleRegister
{
    /**
     * Регистрирует переданные поставщики услуг.
     *
     * @param array $providers Список поставщиков услуг для регистрации.
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function registerServiceProviders(array $providers)
    {
        foreach ($providers as $provider) {
            $classPath = self::normalizeClassPath($provider['class']);

            if( !class_exists(self::normalizeClassPath($classPath)) ) {
                logs('modules')->error("Module $classPath wasn't found in the FileSystem!");
                continue;
            }

            $module = self::instantiateClass($classPath);

            /** @var ModuleServiceProvider */
            $class = app()
                // ->serviceProvider($module)
                ->get($classPath);

            $class->setModuleName($provider['module']);

            $container = app()->getContainer();

            $class->register($container);

            if ($provider['active']) {
                $class->boot($container);

                // Если вдруг нам не надо вызывать расширения, то мы не будем настаивать
                if ($class->isExtensionsCallable()) {
                    foreach ($class->extensions ?? [] as $extension) {
                        $extension = self::instantiateClass($extension);
                        $extension->register();
                    }
                }
            }
        }
    }

    /**
     * Создает экземпляр класса по его пути.
     * 
     * @param string $classPath Путь к классу.
     * @return object Новый экземпляр класса.
     */
    protected static function instantiateClass(string $classPath): object
    {
        $path = self::normalizeClassPath($classPath);
        return new $path;
    }

    /**
     * Нормализует путь к классу, заменяя '/' на '\'.
     * 
     * @param string $class Исходный путь к классу.
     * @return string Нормализованный путь к классу.
     */
    protected static function normalizeClassPath(string $class): string
    {
        return str_replace('/', '\\', $class);
    }
}
