<?php

namespace Flute\Admin\Packages\Currency;

use Flute\Admin\Support\AbstractAdminPackage;

class CurrencyPackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize() : void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/sass/currency.scss');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions() : array
    {
        return ['admin', 'admin.currency'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems() : array
    {
        return [
            [
                'title' => __('admin-currency.title.list'),
                'icon' => 'ph.bold.money-bold',
                'url' => url('/admin/currency'),
            ],
            [
                'type' => 'header',
                'title' => __('def.other'),
            ],
        ];
    }

    public function getPriority() : int
    {
        return 18;
    }
}
