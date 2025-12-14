<?php

namespace Flute\Core\Modules\Profile\Services;

use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Profile\Support\ProfileTab;
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
            })
            ->values();
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
