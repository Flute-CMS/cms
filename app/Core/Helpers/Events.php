<?php

use Flute\Core\Support\FluteEventDispatcher;

if( !function_exists("events") )
{
    /**
     * Get the events instance
     * 
     * @return FluteEventDispatcher
     */
    function events() : FluteEventDispatcher
    {
        return app()->get(FluteEventDispatcher::class);
    }
}