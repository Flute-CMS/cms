<?php

use Flute\Core\Services\DbModService;

if( !function_exists("dbmode") )
{
    function dbmode() : DbModService
    {
        return app(DbModService::class);
    }
}