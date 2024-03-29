<?php

use Flute\Core\Services\FlashService;

if( !function_exists("flash") )
{
    /**
     * Returns the flash service
     * 
     * @return FlashService
     */
    function flash() : FlashService
    {
        return app(FlashService::class);
    }
}
