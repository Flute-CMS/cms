<?php

namespace Flute\Core\Services;

use Flute\Core\Database\Entities\NavbarItem;
use Flute\Core\Navbar\NavbarItemFormat;
use Jenssegers\Agent\Agent;

class NavbarService
{
    public const CACHE_KEY = 'flute.navbar.items';

    public const CACHE_TAG = 'navbar';

    protected const CACHE_TIME = 24 * 60 * 60;

    protected ?array $cachedNavbarItems = null;

    protected bool $performance;

    protected $navbarItemRepository;

    private NavbarItemFormat $format;

    private Agent $agent;

    public function __construct(NavbarItemFormat $format, Agent $agent)
    {
        $this->format = $format;
        $this->agent = $agent;

        if (is_installed()) {
            $this->performance = is_performance();
        } else {
            $this->performance = false;
        }
    }

    /**
     * Lazily loads navbar items from cache/DB on first access.
     */
    protected function loadItems(): array
    {
        if ($this->cachedNavbarItems !== null) {
            return $this->cachedNavbarItems;
        }

        if (!is_installed()) {
            $this->cachedNavbarItems = [];

            return $this->cachedNavbarItems;
        }

        $cacheKey =
            self::CACHE_KEY
            . '.'
            . ( user()->isLoggedIn() ? user()->id : 'guest' )
            . '.'
            . ( $this->agent->isMobile() ? 'mobile' : 'desktop' )
            . '.'
            . app()->getLang();

        $cacheTime = is_development() ? 30 : self::CACHE_TIME;
        cache()->tagKey(self::CACHE_TAG, $cacheKey);
        $this->cachedNavbarItems = cache()->callback($cacheKey, fn() => $this->getDefaultNavbarItems(), $cacheTime);

        return $this->cachedNavbarItems;
    }

    /**
     * Add navbar item to cached items if user has access to it
     *
     * @param NavbarItem $item Navbar item to add
     */
    public function add(NavbarItem $item): self
    {
        $this->loadItems();

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
        return $ignoreAuth ? $this->getDefaultNavbarItems(true) : $this->loadItems();
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

        if (count($item->roles) === 0) {
            return true;
        }

        $highestPriority = user()->getHighestPriority();

        foreach ($item->roles as $role) {
            if (user()->hasRole($role->name) || $highestPriority > $role->priority) {
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
            ->load(['roles', 'parent'])
            ->orderBy('position', 'asc')
            ->fetchAll();

        return $this->buildTree($allItems, $ignoreAuth);
    }

    protected function buildTree(array $items, bool $ignoreAuth = false): array
    {
        $byParent = [];

        foreach ($items as $item) {
            if (!$ignoreAuth && !$this->hasAccess($item)) {
                continue;
            }

            $parentId = $item->parent ? $item->parent->id : 0;
            $byParent[$parentId][] = $item;
        }

        return $this->buildSubtree($byParent, 0, $ignoreAuth);
    }

    protected function buildSubtree(array &$byParent, int $parentId, bool $ignoreAuth): array
    {
        if (!isset($byParent[$parentId])) {
            return [];
        }

        $tree = [];

        foreach ($byParent[$parentId] as $item) {
            $children = $this->buildSubtree($byParent, $item->id, $ignoreAuth);
            $tree[] = $this->format->format($item, $children);
        }

        usort($tree, static fn($a, $b) => ( $a['position'] ?? 0 ) <=> ( $b['position'] ?? 0 ));

        return $tree;
    }
}
