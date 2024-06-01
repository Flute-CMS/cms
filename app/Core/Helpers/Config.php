<?php

use Flute\Core\Services\ConfigurationService;
use League\Config\Configuration;
use League\Config\Exception\UnknownOptionException;

if (!function_exists("config")) {
    /**
     * Get the files instance
     * 
     * @return Configuration|mixed
     */
    function config(string $key = null, $default = null)
    {
        try {
            /** @var Configuration $configs */
            $configs = app(ConfigurationService::class)->getConfiguration();

            return $key ?
                ($configs->get($key) ?? $default) :
                $configs;
        } catch (UnknownOptionException $e) {
            logs()->error($e);

            return $default;
        }
    }
}
