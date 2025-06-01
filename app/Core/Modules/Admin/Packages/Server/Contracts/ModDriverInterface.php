<?php

namespace Flute\Admin\Packages\Server\Contracts;

interface ModDriverInterface
{
    /**
     * Get the driver name.
     */
    public function getName() : string;

    /**
     * Get the settings view for this driver.
     */
    public function getSettingsView() : string;

    /**
     * Get validation rules for this driver's settings.
     */
    public function getValidationRules() : array;
} 