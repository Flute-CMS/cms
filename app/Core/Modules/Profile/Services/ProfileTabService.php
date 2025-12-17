<?php

namespace Flute\Core\Modules\Profile\Services;

use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Profile\Support\ProfileTab;
use Illuminate\Support\Collection;

class ProfileTabService
{
    private const CACHE_KEY = 'profile_tabs_cache';

    /**
     * Collection of registered tabs.
     *
     * @var Collection
     */
    protected $tabs;

    public function __construct()
    {
        $this->tabs = collect();
    }

    /**
     * Registers a new tab.
     */
    public function registerTab(ProfileTab $tab)
    {
        $this->tabs->push($tab);
    }

    /**
     * Caches current tabs for admin panel use.
     * Should be called when profile page is visited (all modules loaded).
     */
    public function cacheTabsForAdmin(): void
    {
        $tabsData = $this->tabs
            ->sortByDesc(static fn (ProfileTab $tab) => $tab->getOrder())
            ->groupBy(static fn (ProfileTab $tab) => $tab->getPath())
            ->map(static function (Collection $group, string $path) {
                $highestPriorityTab = $group->first();

                return [
                    'id' => $path,
                    'path' => $path,
                    'title' => $highestPriorityTab->getTitle(),
                    'icon' => $highestPriorityTab->getIcon(),
                ];
            })
            ->values()
            ->toArray();

        cache()->set(self::CACHE_KEY, $tabsData, 86400 * 30);
    }

    /**
     * Returns cached tabs for admin panel.
     */
    public function getCachedTabs(): array
    {
        return cache()->get(self::CACHE_KEY, []);
    }

    /**
     * Returns all registered tabs, adjusted and sorted by custom order from config or default priority.
     *
     * @return Collection|ProfileTab[]
     */
    public function getTabs(): Collection
    {
        $customOrder = config('profile.tabs_order', []);

        $grouped = $this->tabs
            ->sortByDesc(static fn (ProfileTab $tab) => $tab->getOrder())
            ->groupBy(static fn (ProfileTab $tab) => $tab->getPath())
            ->map(static function (Collection $group) {
                $highestPriorityTab = $group->first();

                return [
                    'path' => $highestPriorityTab->getPath(),
                    'description' => $highestPriorityTab->getDescription(),
                    'title' => $highestPriorityTab->getTitle(),
                    'icon' => $highestPriorityTab->getIcon(),
                ];
            });

        if (!empty($customOrder)) {
            $ordered = collect();
            foreach ($customOrder as $path) {
                if ($grouped->has($path)) {
                    $ordered->put($path, $grouped->get($path));
                }
            }
            foreach ($grouped as $path => $tab) {
                if (!$ordered->has($path)) {
                    $ordered->put($path, $tab);
                }
            }

            return $ordered->values();
        }

        return $grouped->values();
    }

    /**
     * Returns all registered tabs without grouping (for admin panel).
     *
     * @return Collection|ProfileTab[]
     */
    public function getAllRegisteredTabs(): Collection
    {
        return $this->tabs;
    }

    /**
     * Returns unique tab paths for sorting in admin panel.
     * Uses cached tabs if available, otherwise falls back to currently registered.
     */
    public function getUniqueTabPaths(): Collection
    {
        $customOrder = config('profile.tabs_order', []);
        $cachedTabs = $this->getCachedTabs();

        // Use cached tabs if available, otherwise use currently registered
        if (!empty($cachedTabs)) {
            $grouped = collect($cachedTabs)->keyBy('path');
        } else {
            $grouped = $this->tabs
                ->sortByDesc(static fn (ProfileTab $tab) => $tab->getOrder())
                ->groupBy(static fn (ProfileTab $tab) => $tab->getPath())
                ->map(static function (Collection $group, string $path) {
                    $highestPriorityTab = $group->first();

                    return [
                        'id' => $path,
                        'path' => $path,
                        'title' => $highestPriorityTab->getTitle(),
                        'icon' => $highestPriorityTab->getIcon(),
                    ];
                });
        }

        if (!empty($customOrder)) {
            $ordered = collect();

            foreach ($customOrder as $path) {
                if ($grouped->has($path)) {
                    $ordered->put($path, $grouped->get($path));
                }
            }

            foreach ($grouped as $path => $tab) {
                if (!$ordered->has($path)) {
                    $ordered->put($path, $tab);
                }
            }

            return $ordered->values();
        }

        return $grouped->values();
    }

    /**
     * Renders all tabs under a given path for a user.
     */
    public function renderTabsByPath(string $path, User $user): string
    {
        $tabs = $this->getTabsByPath($path)->filter(static fn (ProfileTab $tab) => $tab->canView($user));

        $content = '';

        foreach ($tabs as $tab) {
            $content .= $tab->getContent($user);
        }

        return $content;
    }

    /**
     * Returns all tabs under a specific path, sorted by order.
     *
     * @return Collection|ProfileTab[]
     */
    public function getTabsByPath(string $path): Collection
    {
        return $this->tabs->filter(static fn (ProfileTab $tab) => $tab->getPath() === $path)->sortBy(static fn (ProfileTab $tab) => $tab->getOrder());
    }
}
