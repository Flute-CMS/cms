<?php

use DI\DependencyException;
use DI\NotFoundException;
use Flute\Core\Services\BreadcrumbService;

if (!function_exists("breadcrumb")) {
    /**
     * Get the breadcrumb instance
     *
     * @param string|null $key
     * @return BreadcrumbService
     * @throws DependencyException
     * @throws NotFoundException
     */
    function breadcrumb(string $key = null) : BreadcrumbService
    {
        return app(BreadcrumbService::class);
    }
}
