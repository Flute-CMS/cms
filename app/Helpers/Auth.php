<?php

use DI\DependencyException;
use DI\NotFoundException;
use Flute\Core\Modules\Auth\Services\AuthService;

if (!function_exists('auth')) {
    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    function auth() : AuthService
    {
        static $instance = null;

        if ($instance === null) {
            $instance = app(AuthService::class);
        }

        return $instance;
    }
}
