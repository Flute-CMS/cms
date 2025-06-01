<?php

namespace Flute\Admin\Packages\Server\Factories;

use Flute\Admin\Packages\Server\Contracts\ModDriverInterface;
use Flute\Admin\Packages\Server\Drivers\CustomModDriver;

class ModDriverFactory
{
    /**
     * Registered drivers.
     */
    protected array $drivers = [
        'custom' => CustomModDriver::class,
    ];

    /**
     * Register a new mod driver.
     */
    public function register(string $key, string $driverClass) : void
    {
        if (!is_subclass_of($driverClass, ModDriverInterface::class)) {
            throw new \InvalidArgumentException(
                "Driver class must implement ModDriverInterface."
            );
        }

        if (isset($this->drivers[$key])) {
            throw new \InvalidArgumentException(
                "Driver [{$key}] already registered."
            );
        }

        $this->drivers[$key] = $driverClass;
    }

    /**
     * Create a new driver instance.
     */
    public function make(string $key) : ModDriverInterface
    {
        if (!isset($this->drivers[$key])) {
            throw new \InvalidArgumentException(
                "Mod driver [{$key}] not found."
            );
        }

        $driverClass = $this->drivers[$key];
        return new $driverClass();
    }

    /**
     * Get all registered drivers.
     */
    public function getDrivers() : array
    {
        return $this->drivers;
    }

    /**
     * Check if driver exists.
     */
    public function hasDriver(string $key) : bool
    {
        return isset($this->drivers[$key]);
    }
} 