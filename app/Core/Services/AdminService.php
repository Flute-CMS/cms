<?php


namespace Flute\Core\Services;

use Flute\Core\Admin\AdminBuilder;
use Flute\Core\Admin\Contracts\AdminBuilderInterface;
use Flute\Core\Admin\Exceptions\BuilderNotFoundException;

class AdminService
{
    protected $adminBuilder;

    public function __construct(AdminBuilder $adminBuilder)
    {
        $this->adminBuilder = $adminBuilder;
    }

    public function getAdminBuilder(): AdminBuilder
    {
        return $this->adminBuilder;
    }

    /**
     * Get the builder from admin builder initializer
     * 
     * @throws BuilderNotFoundException
     * @return AdminBuilderInterface
     */
    public function get(string $name): AdminBuilderInterface
    {
        return $this->adminBuilder->getBuilder($name);
    }

    public function __call(string $method, array $parameters)
    {
        return $this->adminBuilder->$method(...$parameters);
    }
}