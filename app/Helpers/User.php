<?php

use Flute\Core\Database\Entities\User;
use Flute\Core\Services\UserService;

if (!function_exists('user')) {
    /**
     * Get the user service instance.
     *
     * @return UserService|User
     */
    function user() : UserService
    {
        return app(UserService::class);
    }
}
