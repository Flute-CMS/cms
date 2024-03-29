<?php

use Flute\Core\Router\RouteDispatcher;

if( !function_exists("router") )
{
    /**
     * Returns the router instance
     * 
     * @return RouteDispatcher
     */
    function router() : RouteDispatcher
    {
        return app(RouteDispatcher::class);
    }
}