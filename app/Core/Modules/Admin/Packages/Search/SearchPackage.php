<?php

namespace Flute\Admin\Packages\Search;

use Flute\Admin\Packages\Search\Events\AdminSearchEvent;
use Flute\Admin\Packages\Search\Listeners\SidebarSearchListener;
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
}
