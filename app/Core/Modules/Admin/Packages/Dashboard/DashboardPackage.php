<?php

namespace Flute\Admin\Packages\Dashboard;

use Flute\Admin\Support\AbstractAdminPackage;

class DashboardPackage extends AbstractAdminPackage
{
    public function initialize() : void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/scss/dashboard.scss');
    }

    public function getPermissions() : array
    {
        return ['admin'];
    }

    public function getPriority() : int
    {
        return 1;
    }

    public function getMenuItems() : array
    {
        return [
            [
                'type' => 'header',
                'title' => __('admin-main-settings.labels.main'),
            ],
            [
                'title' => __('admin-dashboard.labels.home'),
                'icon' => 'ph.bold.chart-line-up-bold',
                'url' => url('/admin'),
            ],
        ];
    }
}