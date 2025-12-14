<?php

use Flute\Core\Services\DatabaseService;

if( !function_exists("dbservice") )
{
    function dbservice() : DatabaseService
    {
        static $instance = null;

        if ($instance === null) {
            $instance = app(DatabaseService::class);
        }

        return $instance;
    }
}