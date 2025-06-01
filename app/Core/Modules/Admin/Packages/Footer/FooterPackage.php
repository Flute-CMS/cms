<?php

namespace Flute\Admin\Packages\Footer;

use Flute\Admin\Support\AbstractAdminPackage;

class FooterPackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize() : void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');

        $this->loadViews('Resources/views', 'admin-footer');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/sass/footer.scss');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions() : array
    {
        return ['admin', 'admin.footer'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems() : array
    {
        return [
            [
                'title' => __('admin-footer.title'),
                'icon' => 'ph.bold.arrow-square-out-bold',
                'url' => url('/admin/footer'),
            ],
        ];
    }

    public function getPriority() : int
    {
        return 16;
    }
}
