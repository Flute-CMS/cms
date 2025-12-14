<?php

use Flute\Core\Services\NavbarService;

if( !function_exists("navbar") )
{
    /**
     * Returns the navbar service
     * 
     * @return NavbarService
     */
    function navbar() : NavbarService
    {
        return app(NavbarService::class);
    }
}
