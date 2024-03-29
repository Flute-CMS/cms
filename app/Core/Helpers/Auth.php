<?php

use DI\DependencyException;
use DI\NotFoundException;
use Flute\Core\Services\AuthService;

if (!function_exists('auth')) {
    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    function auth() : AuthService
    {
        return app(AuthService::class);
    }
}
