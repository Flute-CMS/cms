<?php

namespace Flute\Core\Services\System\Migrations;

use Flute\Core\Database\Entities\Permission;

class CheckPermissionsMigration
{
    private const PERMISSIONS = [
        ['admin.boss', 'permissions.admin.boss'],
        ['admin', 'permissions.admin'],
        ['admin.stats', 'permissions.admin.stats'],
        ['admin.system', 'permissions.admin.system'],
        ['admin.servers', 'permissions.admin.servers'],
        ['admin.navigation', 'permissions.admin.navigation'],
        ['admin.footer', 'permissions.admin.footer'],
        ['admin.gateways', 'permissions.admin.gateways'],
        ['admin.modules', 'permissions.admin.modules'],
        ['admin.templates', 'permissions.admin.templates'],
        ['admin.roles', 'permissions.admin.roles'],
        ['admin.users', 'permissions.admin.users'],
        ['admin.pages', 'permissions.admin.pages'],
        ['admin.socials', 'permissions.admin.socials'],
        ['admin.notifications', 'permissions.admin.notifications'],
        ['admin.composer', 'permissions.admin.composer'],
        ['admin.translate', 'permissions.admin.translate'],
        ['admin.currency', 'permissions.admin.currency'],
        ['admin.event_testing', 'permissions.admin.event_testing'],
        ['admin.redirects', 'permissions.admin.redirects'],
    ];

    public function run()
    {
        $existingPermissions = $this->getExistingPermissions();
        foreach (self::PERMISSIONS as $perm) {
            if (isset($existingPermissions[$perm[0]])) {
                $this->updatePermission($perm[0], $perm[1]);
            } else {
                $this->createPermission($perm[0], $perm[1]);
            }
        }
    }

    private function getExistingPermissions()
    {
        $permissions = rep(Permission::class)->findAll();
        $existingPermissions = [];
        foreach ($permissions as $permission) {
            $existingPermissions[$permission->name] = $permission->desc;
        }
        return $existingPermissions;
    }

    private function createPermission(string $name, string $translationKey)
    {
        $permission = new Permission;
        $permission->name = $name;
        $permission->desc = $translationKey;
        $this->persistEntity($permission);
    }

    private function updatePermission(string $name, string $translationKey)
    {
        $permission = rep(Permission::class)->findOne(['name' => $name]);
        if ($permission && $permission->desc !== $translationKey) {
            $permission->desc = $translationKey;
            $this->persistEntity($permission);
        }
    }

    private function persistEntity($entity): void
    {
        transaction($entity)->run();
    }
}
