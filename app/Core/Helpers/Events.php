<?php

use Symfony\Component\EventDispatcher\EventDispatcher;

if( !function_exists("events") )
{
    /**
     * Get the events instance
     * 
     * @return EventDispatcher
     */
    function events() : EventDispatcher
    {
        return app()->get(EventDispatcher::class);
    }
}