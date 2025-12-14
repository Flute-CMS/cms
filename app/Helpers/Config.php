<?php

use Flute\Core\Services\ConfigurationService;

if (!function_exists("config")) {
    /**
     * Get the files instance
     * 
     * @return ConfigurationService|mixed
     */
    function config(string $key = null, $default = null)
    {
        /** @var ConfigurationService $instance */
        static $instance = null;

        if ($instance === null) {
            $instance = app(ConfigurationService::class);
        }

        return $key ?
            ($instance->get($key) ?? $default) :
            $instance;
    }
}
