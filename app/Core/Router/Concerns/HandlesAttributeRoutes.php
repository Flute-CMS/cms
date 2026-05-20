<?php

namespace Flute\Core\Router\Concerns;

use Flute\Core\Router\AttributeRouteLoader;

trait HandlesAttributeRoutes
{
    private ?AttributeRouteLoader $attributeRouteLoader = null;

    public function registerAttributeRoutes(array $directories, string $namespace): int
    {
        return $this->getAttributeRouteLoader()->loadFromDirectories($directories, $namespace);
    }

    public function registerAttributeRoutesFromClass(string $controllerClass): int
    {
        return $this->getAttributeRouteLoader()->loadFromClass($controllerClass);
    }

    private function getAttributeRouteLoader(): AttributeRouteLoader
    {
        return $this->attributeRouteLoader ??= new AttributeRouteLoader($this);
    }
}
