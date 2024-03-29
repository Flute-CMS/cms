<?php

use Flute\Core\Services\ProfileService;

if( !function_exists("profile") )
{
    function profile() : ProfileService
    {
        return app(ProfileService::class);
    }
}
