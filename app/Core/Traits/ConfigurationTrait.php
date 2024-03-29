<?php

namespace Flute\Core\Traits;

use League\Config\ConfigurationInterface;

trait ConfigurationTrait
{
    /**
     * @var array
     */
    protected array $config;

    /**
     * Set configuration instance
     *
     * @param ConfigurationInterface $configuration
     * @return self
     */
    public function setConfiguration(ConfigurationInterface $configuration): self
    {
        $this->configuration = $configuration;

        return $this;
    }

    /**
     * Get configuration instance
     * 
     * @return ConfigurationInterface
     */
    public function getConfiguration(): ConfigurationInterface
    {
        return $this->configuration;
    }
}