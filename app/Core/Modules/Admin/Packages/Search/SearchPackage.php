<?php

namespace Flute\Admin\Packages\Search;

use Flute\Admin\Packages\Search\Events\AdminSearchEvent;
use Flute\Admin\Packages\Search\Listeners\SidebarSearchListener;
use Flute\Admin\Packages\Search\Services\SlashCommandsRegistry;
use Flute\Admin\Support\AbstractAdminPackage;

class SearchPackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');
        $this->loadTranslations('Resources/lang');

        $this->loadViews('Resources/views', 'admin-search');

        events()->addListener(AdminSearchEvent::NAME, [SidebarSearchListener::class, 'handle']);

        $this->registerCommands();
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return ['admin'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems(): array
    {
        return [];
    }

    public function getPriority(): int
    {
        return 0;
    }

    /**
     * Register slash commands
     */
    protected function registerCommands(): void
    {
        SlashCommandsRegistry::register('user', __('search.search_users'), 'ph.regular.users');
        SlashCommandsRegistry::register('page', __('search.tip_pages'), 'ph.regular.file-text');
        SlashCommandsRegistry::register('server', __('search.tip_servers'), 'ph.regular.hard-drives');
        SlashCommandsRegistry::register('settings', __('search.tip_settings'), 'ph.regular.gear');
    }
}
