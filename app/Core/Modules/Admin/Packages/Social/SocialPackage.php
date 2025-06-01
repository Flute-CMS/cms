<?php

namespace Flute\Admin\Packages\Social;

use Flute\Admin\Support\AbstractAdminPackage;

class SocialPackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize() : void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');

        $this->loadViews('Resources/views', 'admin-social');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/sass/social.scss');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions() : array
    {
        return ['admin', 'admin.socials'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems() : array
    {
        return [
            [
                'title' => __('admin-social.title.social'),
                'icon' => 'ph.bold.globe-simple-bold',
                'url' => url('/admin/socials'),
            ],
        ];
    }

    public function getPriority(): int
    {
        return 11;
    }
}
