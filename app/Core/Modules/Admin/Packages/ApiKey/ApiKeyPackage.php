<?php

namespace Flute\Admin\Packages\ApiKey;

use Flute\Admin\Support\AbstractAdminPackage;

class ApiKeyPackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize() : void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');

        $this->loadViews('Resources/views', 'admin-api');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/sass/api-keys.scss');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions() : array
    {
        return ['admin', 'admin.boss'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems() : array
    {
        return [
            [
                'title' => __('admin-apikey.title.list'),
                'icon' => 'ph.bold.key-bold',
                'url' => url('/admin/api-keys'),
            ],
        ];
    }

    public function getPriority(): int
    {
        return 12;
    }
}
