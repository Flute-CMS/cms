<?php

namespace Flute\Core\Modules\Profile\Services;

use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Profile\Support\ProfileTab;
use Illuminate\Support\Collection;

class ProfileEditTabService
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
    public function register(ProfileTab $tab)
    {
        $this->tabs->push($tab);
    }

    /**
     * Returns all registered tabs, adjusted and sorted by priority.
     *
     * @return Collection|ProfileTab[]
     */
    public function getTabs(?User $user = null): Collection
    {
        $tabs = $user !== null
            ? $this->tabs->filter(static fn (ProfileTab $tab) => $tab->canView($user))
            : $this->tabs;

        return $tabs
            ->sortByDesc(static fn (ProfileTab $tab) => $tab->getOrder())
            ->groupBy(static fn (ProfileTab $tab) => $tab->getPath())
            ->map(static function (Collection $group) {
                $highestPriorityTab = $group->first();

                return [
                    'path' => $highestPriorityTab->getPath(),
                    'description' => $highestPriorityTab->getDescription(),
                    'title' => $highestPriorityTab->getTitle(),
                    'icon' => $highestPriorityTab->getIcon(),
                    'isFullWidth' => $highestPriorityTab->isFullWidth(),
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
            if ($styles = $tab->getStyles()) {
                template()->addStyle($styles);
            }

            if ($scripts = $tab->getScripts()) {
                template()->addScript($scripts);
            }

            $tabContent = $tab->getContent($user);

            if ($layout = $tab->getLayout()) {
                $tabContent = view($layout, ['content' => $tabContent])->render();
            }

            $content .= $tabContent;
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
