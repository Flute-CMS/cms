<?php

namespace Flute\Core\Installer\Migrations;

use Flute\Core\Database\Entities\Role;
use Flute\Core\Database\Entities\Permission;
use Throwable;

class RBACInstaller
{
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
        $adminRole = $this->createRole('admin');
        $this->createRole('user');

        // Create permissions
        $permissions = [
            $this->createPermission('admin.boss', 'This permission has all access to all permissions'),

            $this->createPermission('admin', 'Permission to access the admin panel'),

            $this->createPermission('admin.stats', 'Permission to view financial stats'),

            $this->createPermission('admin.system', 'Permission to change system settings'),
            $this->createPermission('admin.servers', 'Permission to change server'),
            $this->createPermission('admin.navigation', 'Permission to CRUD navigation'),
            $this->createPermission('admin.footer', 'Permission to CRUD footer socials'),
            $this->createPermission('admin.gateways', 'Permission to CRUD some payments'),
            $this->createPermission('admin.modules', 'Permission to CRUD modules'),
            $this->createPermission('admin.templates', 'Permission to CRUD templates'),
            $this->createPermission('admin.roles', 'Permission to CRUD roles to users'),
            $this->createPermission('admin.users', 'Permission to manipulate users'),
            $this->createPermission('admin.pages', 'Permission to CRUD pages'),
            $this->createPermission('admin.socials', 'Permission to CRUD socials'),
            $this->createPermission('admin.notifications', 'Permission to create user notifications'),
        ];

        // Assign permissions to admin role
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
    protected function createRole(string $name): Role
    {
        $role = new Role;
        $role->name = $name;
        $role->priority = 1;
        $this->persistEntity($role);
        return $role;
    }

    /**
     * Create a permission.
     *
     * @param string $name The name of the permission.
     * @param string $desc The description of the permission.
     * @return Permission The created permission.
     * @throws Throwable
     */
    protected function createPermission(string $name, string $desc): Permission
    {
        $permission = new Permission;
        $permission->name = $name;
        $permission->desc = $desc;
        $this->persistEntity($permission);
        return $permission;
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
