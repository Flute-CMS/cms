<?php

namespace Flute\Core\Services;

use Flute\Core\App;
use Flute\Core\Database\Entities\NavbarItem;
use Flute\Core\Navbar\NavbarItemFormat;

class NavbarService
{
    private NavbarItemFormat $format; // Class to format navbar items
    protected array $cachedNavbarItems; // Array to hold navbar items
    protected bool $performance;
    protected const CACHE_TIME = 24 * 60 * 60;
    public const CACHE_KEY = 'flute.navbar.items';

    /**
     * Constructor method
     * Initializes the format class and sets the default navbar items
     */
    public function __construct(NavbarItemFormat $format)
    {
        if (!is_installed())
            return;

        $this->performance = (bool) (app('app.mode') == App::PERFORMANCE_MODE);

        $this->format = $format;

        $this->cachedNavbarItems = $this->performance ? cache()->callback(self::CACHE_KEY, function () {
            return $this->getDefaultNavbarItems();
        }, self::CACHE_TIME) : $this->getDefaultNavbarItems();
    }

    /**
     * Adds a navbar item to the cached items, if the user has access to it
     *
     * @param NavbarItem $item The item to add
     *
     * @return self
     */
    public function add(NavbarItem $item): self
    {
        if ($this->hasAccess($item)) {
            $this->cachedNavbarItems[] = $this->format->format($item);
        }

        return $this;
    }

    /**
     * Returns all cached navbar items
     *
     * @return array
     */
    public function all(): array
    {
        return $this->cachedNavbarItems;
    }

    /**
     * Sets the default navbar items by fetching them from the database
     */
    protected function getDefaultNavbarItems(): array
    {
        $repository = rep(NavbarItem::class);
        $navbarItems = $repository->select()->load('roles')->orderBy('position', 'asc')->where([
            'parent_id' => null,
        ])->fetchAll();

        $formattedItems = [];

        foreach ($navbarItems as $item) {
            if ($this->hasAccess($item)) {
                $formattedItem = $this->format->format($item);
                $formattedItem['children'] = $this->getChildren($item->id);
                $formattedItems[] = $formattedItem;
            }
        }

        return $formattedItems;
    }

    /**
     * Recursively fetch and format child navbar items
     *
     * @param int $parentId The ID of the parent navbar item
     *
     * @return array
     */
    protected function getChildren(int $parentId): array
    {
        $repository = rep(NavbarItem::class);
        $children = $repository->select()->load('roles')->orderBy('position', 'asc')->where([
            'parent_id' => $parentId,
        ])->fetchAll();

        $formattedChildren = [];

        foreach ($children as $child) {
            if ($this->hasAccess($child)) {
                $formattedChild = $this->format->format($child);
                $formattedChild['children'] = $this->getChildren($child->id);
                $formattedChildren[] = $formattedChild;
            }
        }

        return $formattedChildren;
    }


    /**
     * Checks if the user has access to a navbar item
     *
     * @param NavbarItem $item The navbar item to check
     *
     * @return bool
     */
    public function hasAccess(NavbarItem $item): bool
    {
        $isLoggedIn = user()->isLoggedIn();

        // Item visibility constraints
        if ($item->visibleOnlyForGuests && $isLoggedIn) {
            return false;
        }

        if ($item->visibleOnlyForLoggedIn && !$isLoggedIn) {
            return false;
        }

        // If no roles are specified, the item is accessible for logged in user
        if (sizeof($item->roles) === 0) {
            return true;
        }

        // If user is logged in and has any of the roles required for the item
        foreach ($item->roles as $role) {
            if ((user()->hasRole($role->name) || user()->getHighestPriority() > $role->priority)) {
                return true;
            }
        }

        // By default, the item is not accessible
        return false;
    }
}