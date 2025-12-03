<?php

namespace Flute\Core\Services;

use Flute\Core\Database\Entities\NavbarItem;
use Flute\Core\Navbar\NavbarItemFormat;
use Jenssegers\Agent\Agent;

class NavbarService
{
    public const CACHE_KEY = 'flute.navbar.items';

    protected const CACHE_TIME = 24 * 60 * 60;

    protected array $cachedNavbarItems;

    protected bool $performance;

    protected $navbarItemRepository;

    private NavbarItemFormat $format;

    private Agent $agent;

    public function __construct(NavbarItemFormat $format, Agent $agent)
    {
        if (!is_installed()) {
            return;
        }

        $this->performance = is_performance();

        $this->format = $format;
        $this->agent = $agent;

        $cacheKey = self::CACHE_KEY . '.' . (user()->isLoggedIn() ? user()->id : 'guest') . '.' . ($this->agent->isMobile() ? 'mobile' : 'desktop') . '.' . app()->getLang();

        $this->cachedNavbarItems = cache()->callback($cacheKey, fn () => $this->getDefaultNavbarItems(), self::CACHE_TIME);
    }

    /**
     * Add navbar item to cached items if user has access to it
     *
     * @param NavbarItem $item Navbar item to add
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
     */
    public function all(bool $ignoreAuth = false): array
    {
        return $ignoreAuth ? $this->getDefaultNavbarItems(true) : $this->cachedNavbarItems;
    }

    /**
     * Checks if user has access to navbar item
     *
     * @param NavbarItem $item Navbar item to check
     * @param bool $ignoreAuth Ignore auth rules
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

    /**
     * Sets default navbar items, getting them from the database
     *
     * @param bool $ignoreAuth Ignore auth rules
     */
    protected function getDefaultNavbarItems(bool $ignoreAuth = false): array
    {
        $allItems = NavbarItem::query()
            ->load(['roles', 'parent', 'children', 'children.roles'])
            ->orderBy('position', 'asc')
            ->fetchAll();

        $tree = $this->buildTree($allItems, null, $ignoreAuth);

        return $tree;
    }

    protected function buildTree(array $items, ?int $parentId = null, bool $ignoreAuth = false): array
    {
        $tree = [];

        foreach ($items as $item) {
            if (($parentId === null) === ($item->parent === null) && ($parentId === null || ($item->parent && $item->parent->id === $parentId)) && ($ignoreAuth || $this->hasAccess($item))) {
                $formatted = $this->format->format($item);
                $formatted['children'] = $this->buildTree($items, $item->id, $ignoreAuth);
                $tree[] = $formatted;
            }
        }

        usort($tree, static fn ($a, $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

        return $tree;
    }
}
