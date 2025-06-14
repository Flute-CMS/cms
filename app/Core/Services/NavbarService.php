<?php

namespace Flute\Core\Services;

use Flute\Core\Database\Entities\NavbarItem;
use Flute\Core\Navbar\NavbarItemFormat;
use Jenssegers\Agent\Agent;

class NavbarService
{
    private NavbarItemFormat $format;
    protected array $cachedNavbarItems;
    protected bool $performance;
    protected $navbarItemRepository;
    protected const CACHE_TIME = 24 * 60 * 60;
    public const CACHE_KEY = 'flute.navbar.items';

    private Agent $agent;

    public function __construct(NavbarItemFormat $format, Agent $agent)
    {
        if (!is_installed())
            return;

        $this->performance = is_performance();

        $this->format = $format;
        $this->agent = $agent;

        $cacheKey = self::CACHE_KEY . '.' . (user()->isLoggedIn() ? user()->id : 'guest') . '.' . ($this->agent->isMobile() ? 'mobile' : 'desktop') . '.' . app()->getLang();

        $this->cachedNavbarItems = $this->performance
            ? cache()->callback($cacheKey, function () {
                return $this->getDefaultNavbarItems();
            }, self::CACHE_TIME)
            : $this->getDefaultNavbarItems();
    }

    /**
     * Add navbar item to cached items if user has access to it
     *
     * @param NavbarItem $item Navbar item to add
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
     * @param bool $ignoreAuth Ignore auth rules
     *
     * @return array
     */
    public function all(bool $ignoreAuth = false): array
    {
        return $ignoreAuth ? $this->getDefaultNavbarItems(true) : $this->cachedNavbarItems;
    }

    /**
     * Sets default navbar items, getting them from the database
     *
     * @param bool $ignoreAuth Ignore auth rules
     *
     * @return array
     */
    protected function getDefaultNavbarItems(bool $ignoreAuth = false): array
    {
        $navbarItems = NavbarItem::query()
            ->load(['roles', 'children', 'children.roles'])
            ->orderBy('position', 'asc')
            ->where([
                'parent_id' => null,
            ])->fetchAll();

        $formattedItems = [];

        foreach ($navbarItems as $item) {
            if ($this->hasAccess($item, $ignoreAuth)) {
                $formattedItem = $this->format->format($item);
                $formattedItem['children'] = $this->formatChildren($item->children, $ignoreAuth);
                $formattedItems[] = $formattedItem;
            }
        }

        return $formattedItems;
    }

    /**
     * Format children items without additional database queries
     *
     * @param array $children Children items already loaded
     * @param bool $ignoreAuth Ignore auth rules
     *
     * @return array
     */
    protected function formatChildren(array $children, bool $ignoreAuth = false): array
    {
        $formattedChildren = [];

        foreach ($children as $child) {
            if ($this->hasAccess($child, $ignoreAuth)) {
                $formattedChild = $this->format->format($child);
                // Recursively format children's children if they exist
                if (!empty($child->children)) {
                    $formattedChild['children'] = $this->formatChildren($child->children, $ignoreAuth);
                } else {
                    $formattedChild['children'] = [];
                }
                $formattedChildren[] = $formattedChild;
            }
        }

        return $formattedChildren;
    }

    /**
     * Checks if user has access to navbar item
     *
     * @param NavbarItem $item Navbar item to check
     * @param bool $ignoreAuth Ignore auth rules
     *
     * @return bool
     */
    public function hasAccess(NavbarItem $item, bool $ignoreAuth = false): bool
    {
        $isLoggedIn = user()->isLoggedIn();
        $isMobile = $this->agent->isMobile();

        if (!$ignoreAuth) {
            if ($item->visibleOnlyForGuests && $isLoggedIn) {
                return false;
            }

            if ($item->visibleOnlyForLoggedIn && !$isLoggedIn) {
                return false;
            }
        }

        switch ($item->visibility) {
            case 'desktop':
                if ($isMobile) {
                    return false;
                }
                break;
            case 'mobile':
                if (!$isMobile) {
                    return false;
                }
                break;
            case 'all':
            default:
                break;
        }

        if (sizeof($item->roles) === 0) {
            return true;
        }

        foreach ($item->roles as $role) {
            if ((user()->hasRole($role->name) || user()->getHighestPriority() > $role->priority)) {
                return true;
            }
        }

        return false;
    }
}
