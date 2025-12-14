<?php

namespace Flute\Core\Modules\Installer\Migrations;

use Flute\Core\Database\Entities\NavbarItem;
use Flute\Core\Database\Entities\Role;
use Throwable;

class NavbarInstaller
{
    /**
     * Install default roles and permissions.
     * @throws Throwable
     */
    public function initDefaultItems()
    {
        $this->clearItems();

        $role = Role::findOne(['name' => 'admin']);

        $this->createItem('def.home', '/', 'ph ph-house');
        $this->createItem('def.admin_panel', '/admin/', 'ph ph-gear', false, false, true, $role);
    }

    /**
     * Clear all navbar items
     */
    protected function clearItems(): void
    {
        db()->delete('navbar_items')->run();
    }

    /**
     * @throws Throwable
     */
    protected function createItem(
        string $title,
        string $url,
        string $icon,
        bool $newTab = false,
        bool $visibleForGuests = false,
        bool $visibleForLoggedIn = false,
        ?Role $role = null
    ): NavbarItem {
        $navbarItem = new NavbarItem();
        $navbarItem->title = $title;
        $navbarItem->url = $url;
        $navbarItem->icon = $icon;
        $navbarItem->new_tab = $newTab;
        $navbarItem->visibleOnlyForGuests = $visibleForGuests;
        $navbarItem->visibleOnlyForLoggedIn = $visibleForLoggedIn;

        if ($role) {
            $navbarItem->addRole($role);
        }

        $this->persistEntity($navbarItem);

        return $navbarItem;
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
