<?php

namespace Flute\Admin\Packages\AboutSystem;

use Flute\Admin\Support\AbstractAdminPackage;

class AboutSystemPackage extends AbstractAdminPackage
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadViews('Resources/views', 'admin-about-system');

        $this->loadRoutesFromFile('routes.php');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/scss/about-system.scss');
    }

    public function getPermissions(): array
    {
        return ['admin', 'admin.system'];
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function getMenuItems(): array
    {
        return [
            [
                'key' => 'about',
                'title' => __('admin-about-system.labels.home'),
                'icon' => 'ph.regular.question',
                'url' => url('/admin/about-system'),
            ],
        ];
    }
}
