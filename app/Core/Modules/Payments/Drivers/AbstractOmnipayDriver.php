<?php

namespace Flute\Core\Modules\Payments\Drivers;

use Flute\Core\Modules\Payments\Contracts\PaymentDriverInterface;

abstract class AbstractOmnipayDriver implements PaymentDriverInterface
{
    /**
     * Adapter name. (e.g. 'PayPal_Express' or 'Stripe')
     */
    public ?string $adapter = null;

    /**
     * Driver name. (For display purposes)
     */
    public ?string $name = null;

    /**
     * Settings view. (For admin panel)
     */
    public ?string $settingsView = null;

    /**
     * Validation rules. (when creating/editing a payment gateway)
     */
    public function getValidationRules(): array
    {
        return [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSettingsView(): string
    {
        return $this->settingsView;
    }

    public function getAdapter(): string
    {
        return $this->adapter;
    }

    public function validateSettings(array $settings): bool
    {
        return true;
    }

    public function getSettings(): array
    {
        return [];
    }
}
