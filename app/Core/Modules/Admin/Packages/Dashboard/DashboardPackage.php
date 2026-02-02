<?php

namespace Flute\Admin\Packages\Dashboard;

use Flute\Admin\Support\AbstractAdminPackage;

class DashboardPackage extends AbstractAdminPackage
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');

        $this->loadViews('Resources/views', 'admin-dashboard');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/scss/dashboard.scss');
    }

    public function getPermissions(): array
    {
        return ['admin'];
    }

    public function getPriority(): int
    {
        return 1;
    }

    public function getMenuItems(): array
    {
        return [
            [
                'key' => 'dashboard',
                'title' => __('admin-dashboard.labels.home'),
                'icon' => 'ph.regular.house',
                'url' => url('/admin'),
            ],
        ];
    }
}
