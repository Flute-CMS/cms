<?php

namespace Flute\Core\Admin;

use Flute\Core\Admin\Builders\AdminGateways\AdminGatewaysBuider;
use Flute\Core\Admin\Builders\AdminSidebarBuilder;
use Flute\Core\Admin\Builders\AdminThemeBuilder;
use Flute\Core\Admin\Contracts\AdminBuilderInterface;
use Flute\Core\Admin\Exceptions\BuilderNotFoundException;
use Flute\Core\Support\FluteRequest;

/**
 * #TODO: 
 * Надо предусмотреть обертку для модулей, для быстрого разворачивания собственных
 * страниц внутри админки, без необходимости @extends в шаблонизаторе. (убьет все.)
 * 
 * Скорее всего в ServiceProvider сделать хелпер для инициализации.
 */

class AdminBuilder
{
    protected array $builders = [
        AdminSidebarBuilder::class,
        AdminThemeBuilder::class,
    ];

    protected array $aliases = [
        'sidebar' => AdminSidebarBuilder::class,
        'theme' => AdminThemeBuilder::class,
    ];

    public function __construct(FluteRequest $request)
    {
        // Если у нас админка не запущена, то нахер нам билдеры билдить
        $path = $request->getPathInfo();

        if (strpos($path, '/admin') !== 0)
            return;

        $this->initBuilders();
    }

    protected function initBuilders(): void
    {
        foreach ($this->builders as $builder) {
            /** 
             * Я специально сделал тут вызов через хелпер app.
             * Это тупо проще, и меня не парит некоторая рекурсия
             * 
             * (Если вызывать напрямую с контейнера, то instance входящих
             * классов будет отличаться. ReflectionClass DI будет создавать
             * новые экземпляры. Поэтому проще всего просто вызывать сразу с App)
             * 
             * @var AdminBuilderInterface
             */
            $builderClass = app($builder);
            $builderClass->build($this);

            // Re-init vars.
            $this->builders[$builder] = $builderClass;
        }
    }

    /**
     * Get the builder from admin builder initializer
     * 
     * @throws BuilderNotFoundException
     * @return AdminBuilderInterface
     */
    public function getBuilder(string $name): AdminBuilderInterface
    {
        return $this->getByAlias($name);
    }

    /**
     * Get from alias builder
     * 
     * @return mixed
     */
    protected function getByAlias(string $alias)
    {
        if (isset($this->builders[$alias]))
            return $this->builders[$alias];

        if (isset($this->aliases[$alias]))
            return $this->builders[$this->aliases[$alias]];

        throw new BuilderNotFoundException($alias);
    }

    public function __call(string $method, array $parameters)
    {
        return $this->getByAlias($method);
    }
}