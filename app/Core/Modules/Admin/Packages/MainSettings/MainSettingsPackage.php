<?php

namespace Flute\Admin\Packages\MainSettings;

use Flute\Admin\Support\AbstractAdminPackage;

class MainSettingsPackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');

        $this->loadViews('Resources/views', 'admin-main-settings');

        $this->registerScss('Resources/assets/scss/main-settings.scss');

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
                'title' => 'admin-main-settings.labels.home',
                'icon' => 'ph.bold.gear-bold',
                'url' => url('/admin/main-settings'),
            ],
        ];
    }

    public function getPriority(): int
    {
        return 2;
    }
}
