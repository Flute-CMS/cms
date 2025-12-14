<?php

namespace Flute\Admin\Packages\Roles;

use Flute\Admin\Support\AbstractAdminPackage;

class RolesPackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');

        $this->loadTranslations('Resources/lang');

        $this->loadViews('Resources/views', 'admin-roles');

        $this->registerScss('Resources/assets/sass/roles.scss');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return ['admin', 'admin.roles'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems(): array
    {
        return [
            [
                'title' => __('admin-roles.title.roles'),
                'icon' => 'ph.bold.shield-bold',
                'url' => url('/admin/roles'),
            ],
        ];
    }

    public function getPriority(): int
    {
        return 11;
    }
}
