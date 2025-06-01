<?php

use Flute\Core\Services\FooterService;

if( !function_exists("footer") )
{
    /**
     * Returns the footer service
     * 
     * @return FooterService
     */
    function footer() : FooterService
    {
        return app(FooterService::class);
    }
}
