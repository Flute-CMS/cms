<?php

use Flute\Core\Services\SessionService;

if( !function_exists("session") )
{
    /**
     * Returns the session instance
     * 
     * @param string $key
     * 
     * @return SessionService|mixed
     */
    function session($key = null, $default = null)
    {
        /** @var SessionService $cookieService */
        $sessionService = app(SessionService::class);

        return $key ? $sessionService->get($key, $default) : $sessionService;
    }
}