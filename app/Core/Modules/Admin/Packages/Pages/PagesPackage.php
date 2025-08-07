<?php

namespace Flute\Admin\Packages\Pages;

use Flute\Admin\Packages\Pages\Listeners\PageSearchListener;
use Flute\Admin\Packages\Search\Services\SlashCommandsRegistry;
use Flute\Admin\Support\AbstractAdminPackage;
use Flute\Core\Database\Entities\Page;

class PagesPackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadViews('Resources/views', 'admin-pages');

        $this->loadRoutesFromFile('routes.php');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/sass/pages.scss');

        SlashCommandsRegistry::register('page', __('admin-pages.search_pages'), 'ph.regular.file-text');

        events()->addSubscriber(new PageSearchListener());
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return ['admin', 'admin.pages'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems(): array
    {
        return [
            [
                'type' => 'header',
                'title' => __('admin-pages.title.content'),
            ],
            [
                'title' => __('admin-pages.title.list'),
                'icon' => 'ph.bold.file-text-bold',
                'url' => url('/admin/pages'),
                'badge' => $this->getPagesCount(),
            ],
        ];
    }

    protected function getPagesCount(): int
    {
        return Page::query()->count();
    }

    public function getPriority(): int
    {
        return 12;
    }
}
