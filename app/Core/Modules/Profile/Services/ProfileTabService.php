<?php

namespace Flute\Core\Modules\Profile\Services;

use Flute\Core\Modules\Profile\Support\ProfileTab;
use Flute\Core\Database\Entities\User;
use Illuminate\Support\Collection;

class ProfileTabService
{
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
     *
     * @param ProfileTab $tab
     */
    public function registerTab(ProfileTab $tab)
    {
        $this->tabs->push($tab);
    }

    /**
     * Returns all registered tabs, adjusted and sorted by priority.
     *
     * @return Collection|ProfileTab[]
     */
    public function getTabs(): Collection
    {
        return $this->tabs
            ->sortByDesc(function (ProfileTab $tab) {
                return $tab->getOrder();
            })
            ->groupBy(function (ProfileTab $tab) {
                return $tab->getPath();
            })
            ->map(function (Collection $group) {
                $highestPriorityTab = $group->first();

                return [
                    'path' => $highestPriorityTab->getPath(),
                    'description' => $highestPriorityTab->getDescription(),
                    'title' => $highestPriorityTab->getTitle(),
                    'icon' => $highestPriorityTab->getIcon()
                ];
            })
            ->values();
    }

    /**
     * Renders all tabs under a given path for a user.
     *
     * @param string $path
     * @param User $user
     * @return string
     */
    public function renderTabsByPath(string $path, User $user): string
    {
        $tabs = $this->getTabsByPath($path)->filter(function (ProfileTab $tab) use ($user) {
            return $tab->canView($user);
        });

        $content = '';

        foreach ($tabs as $tab) {
            $content .= $tab->getContent($user);
        }

        return $content;
    }

    /**
     * Returns all tabs under a specific path, sorted by order.
     *
     * @param string $path
     * @return Collection|ProfileTab[]
     */
    public function getTabsByPath(string $path): Collection
    {
        return $this->tabs->filter(function (ProfileTab $tab) use ($path) {
            return $tab->getPath() === $path;
        })->sortBy(function (ProfileTab $tab) {
            return $tab->getOrder();
        });
    }
}
