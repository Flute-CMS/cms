<?php

namespace Flute\Core\Modules\Installer\Migrations;

use Flute\Core\Database\Entities\Permission;
use Flute\Core\Database\Entities\Role;
use Flute\Core\SystemHealth\Migrations\CheckPermissionsMigration;
use Throwable;

class RBACInstaller
{
    private $permissionsManager;

    public function __construct()
    {
        $this->permissionsManager = new CheckPermissionsMigration;
    }

    /**
     * Install default roles and permissions.
     * @throws Throwable
     */
    public function installDefaultRolesAndPermissions()
    {
        try {
            $this->clearUsers();
            $this->clearRoles();
            $this->clearPermissions();
        } catch (\Exception $e) {
            // Ignore error
        }

        // Create roles
        $adminRole = $this->createRole('admin', "#BAFF68");
        $this->createRole('user');

        // Ensure permissions exist
        $this->permissionsManager->run();

        // Assign permissions to admin role
        $permissions = rep(Permission::class)->findAll();
        $this->assignPermissions($adminRole, $permissions);
    }

    /**
     * Clear all roles
     * 
     * @return void
     */
    protected function clearRoles(): void
    {
        db()->delete('roles')->run();
    }

    /**
     * Clear all permissions
     * 
     * @return void
     */
    protected function clearPermissions(): void
    {
        db()->delete('permissions')->run();
    }

    /**
     * Clear all users
     * 
     * @return void
     */
    protected function clearUsers(): void
    {
        db()->delete('users')->run();
    }

    /**
     * Create a role.
     *
     * @param string $name The name of the role.
     * @return Role The created role.
     * @throws Throwable
     */
    protected function createRole(string $name, string $color = "#ffffff"): Role
    {
        $role = new Role;
        $role->name = $name;
        $role->priority = 1;
        $role->color = $color;
        $this->persistEntity($role);
        return $role;
    }

    /**
     * Assign permissions to a role.
     *
     * @param Role $role The role to assign permissions to.
     * @param array $permissions The permissions to assign.
     * @throws Throwable
     */
    protected function assignPermissions(Role $role, array $permissions): void
    {
        foreach ($permissions as $permission) {
            $role->addPermission($permission);
        }
        $this->persistEntity($role);
    }

    /**
     * Persist an entity.
     *
     * @param mixed $entity The entity to persist.
     * @throws Throwable
     */
    protected function persistEntity($entity): void
    {
        transaction($entity)->run();
    }
}
