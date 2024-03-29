<?php

use Flute\Core\Services\UserService;

if (!function_exists('user')) {
    function user() : UserService
    {
        return app(UserService::class);
    }
}
