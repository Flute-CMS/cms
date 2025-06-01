<?php

namespace Flute\Admin\Packages\Navigation;

use Flute\Admin\Support\AbstractAdminPackage;

class NavigationPackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize() : void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');

        $this->loadViews('Resources/views', 'admin-navigation');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/sass/navigation.scss');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions() : array
    {
        return ['admin', 'admin.navigation'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems() : array
    {
        return [
            [
                'type' => 'header',
                'title' => __('admin-navigation.title'),
            ],
            [
                'title' => __('admin-navigation.title'),
                'icon' => 'ph.bold.list-bold',
                'url' => url('/admin/navigation'),
            ],
        ];
    }

    public function getPriority() : int
    {
        return 15;
    }
}
