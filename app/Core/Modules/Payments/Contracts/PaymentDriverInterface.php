<?php

namespace Flute\Core\Modules\Payments\Contracts;

interface PaymentDriverInterface
{
    /**
     * Get driver validation rules.
     */
    public function getValidationRules() : array;

    /**
     * Get driver name.
     */
    public function getName() : string;

    /**
     * Get driver settings view.
     */
    public function getSettingsView() : string;

    /**
     * Get driver adapter.
     */
    public function getAdapter() : string;

    /**
     * Validate driver settings.
     */
    public function validateSettings(array $settings) : bool;

    /**
     * Get driver settings.
     */
    public function getSettings() : array;
}