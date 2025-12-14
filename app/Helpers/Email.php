<?php

use Flute\Core\Services\EmailService;

if( !function_exists("email") )
{
    function email() : EmailService
    {
        return app(EmailService::class);
    }
}