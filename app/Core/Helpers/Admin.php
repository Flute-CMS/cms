<?php

use DI\DependencyException;
use DI\NotFoundException;
use Flute\Core\Services\AdminService;

if (!function_exists('admin')) {
    /**
     * @throws DependencyException
     * @throws NotFoundException
     * 
     * @return AdminService
     */
    function admin() : AdminService
    {
        return app(AdminService::class);
    }
}
