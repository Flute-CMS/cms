<?php

namespace Flute\Core\Traits;

use DI\Container;
use DI\ContainerBuilder;

trait ContainerTrait
{
    /**
     */
    protected ?Container $container = null;

    /**
     * @return ContainerBuilder
     */
    protected ContainerBuilder $containerBuilder;

    /**
     */
    public function getContainerBuilder(): ContainerBuilder
    {
        return $this->containerBuilder;
    }

    /**
     */
    public function setContainerBuilder(ContainerBuilder $containerBuilder): void
    {
        $this->containerBuilder = $containerBuilder;
    }

    /**
     * Set container instance
     */
    public function setContainer(Container $container): self
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get container instance
     */
    public function getContainer(): Container
    {
        return $this->container;
    }
}
