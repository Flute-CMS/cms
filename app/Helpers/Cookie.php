<?php

use Flute\Core\Services\CookieService;

if (!function_exists("cookie")) {
    /**
     * Returns the cookie instance
     * 
     * @param string $key
     * 
     * @return CookieService|mixed
     */
    function cookie($key = null, $default = null)
    {
        /** @var CookieService $instance */
        static $instance = null;

        if ($instance === null) {
            $instance = app(CookieService::class);
        }

        return $key ? $instance->get($key, $default) : $instance;
    }
}