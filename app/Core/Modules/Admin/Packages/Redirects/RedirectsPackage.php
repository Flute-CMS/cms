<?php

namespace Flute\Admin\Packages\Redirects;

use Flute\Admin\Support\AbstractAdminPackage;

class RedirectsPackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');

        $this->registerScss('Resources/assets/scss/conditions.scss');

        $this->loadViews('Resources/views', 'admin-redirects');

        $this->loadTranslations('Resources/lang');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return ['admin', 'admin.system'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems(): array
    {
        return [
            [
                'key' => 'redirects',
                'title' => __('admin-redirects.title'),
                'icon' => 'ph.regular.arrow-u-down-right',
                'url' => url('/admin/redirects'),
            ],
        ];
    }

    public function getPriority(): int
    {
        return 16;
    }
}
