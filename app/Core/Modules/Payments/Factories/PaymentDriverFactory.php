<?php

namespace Flute\Core\Modules\Payments\Factories;

use Flute\Core\Modules\Payments\Contracts\PaymentDriverInterface;

class PaymentDriverFactory
{
    /**
     * Registered drivers.
     */
    protected array $drivers = [];

    /**
     * Register a new payment driver.
     */
    public function register(string $key, string $driverClass): void
    {
        if (!is_subclass_of($driverClass, PaymentDriverInterface::class)) {
            throw new \InvalidArgumentException(
                "Driver class must implement PaymentDriverInterface."
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
    public function make(string $key): PaymentDriverInterface
    {
        if (!isset($this->drivers[$key])) {
            throw new \InvalidArgumentException(
                "Payment driver [{$key}] not found."
            );
        }

        $driverClass = $this->drivers[$key];

        return new $driverClass();
    }

    /**
     * Get all registered drivers.
     */
    public function getDrivers(): array
    {
        return $this->drivers;
    }

    /**
     * Check if driver exists.
     */
    public function hasDriver(string $key): bool
    {
        return isset($this->drivers[$key]);
    }
}
