<?php

use Flute\Core\App;
use Flute\Core\Services\CookieService;

if( !function_exists("cookie") )
{
    /**
     * Returns the cookie instance
     * 
     * @param string $key
     * 
     * @return CookieService|mixed
     */
    function cookie($key = null, $default = null)
    {
        /** @var CookieService $cookieService */
        $cookieService = App::getInstance()->get(CookieService::class);

        return $key ? $cookieService->get($key, $default) : $cookieService;
    }
}