<?php

use Flute\Core\Services\EncryptService;

if( !function_exists("encrypt") )
{
    function encrypt() : EncryptService
    {
        return app(EncryptService::class);
    }
}