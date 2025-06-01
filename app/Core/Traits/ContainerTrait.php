<?php

namespace Flute\Core\Traits;

use DI\Container;
use DI\ContainerBuilder;

trait ContainerTrait
{
    /**
     * @var ?Container
     */
    protected ?Container $container = null;
    
    /**
     * @return ContainerBuilder
     *
     */
    protected ContainerBuilder $containerBuilder;

    /**
     * @return ContainerBuilder
     */
    public function getContainerBuilder(): ContainerBuilder
    {
        return $this->containerBuilder;
    }

    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function setContainerBuilder(ContainerBuilder $containerBuilder): void
    {
        $this->containerBuilder = $containerBuilder;
    }

    /**
     * Set container instance
     *
     * @param Container $container
     * @return self
     */
    public function setContainer(Container $container): self
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get container instance
     * 
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }
}