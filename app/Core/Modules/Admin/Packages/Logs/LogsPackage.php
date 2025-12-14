<?php

namespace Flute\Admin\Packages\Logs;

use Flute\Admin\Support\AbstractAdminPackage;

class LogsPackage extends AbstractAdminPackage
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadViews('Resources/views', 'admin-logs');

        $this->loadRoutesFromFile('routes.php');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/scss/logs.scss');
    }

    public function getPermissions(): array
    {
        return ['admin.boss'];
    }

    public function getPriority(): int
    {
        return 999;
    }

    public function getMenuItems(): array
    {
        return [
            [
                'type' => 'header',
                'title' => __('admin-logs.labels.main'),
            ],
            [
                'title' => __('admin-logs.title'),
                'icon' => 'ph.bold.list-bullets-bold',
                'url' => url('/admin/logs'),
            ],
        ];
    }
}
