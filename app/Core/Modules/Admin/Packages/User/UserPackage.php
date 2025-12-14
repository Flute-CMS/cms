<?php

namespace Flute\Admin\Packages\User;

use Flute\Admin\Packages\Search\Services\SlashCommandsRegistry;
use Flute\Admin\Packages\User\Listeners\UserSearchListener;
use Flute\Admin\Support\AbstractAdminPackage;

class UserPackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadViews('Resources/views', 'admin-users');

        $this->loadRoutesFromFile('routes.php');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/sass/user.scss');

        SlashCommandsRegistry::register('user', __('search.search_users'), 'ph.regular.user');

        events()->addSubscriber(new UserSearchListener());
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return ['admin', 'admin.users'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems(): array
    {
        return [
            [
                'type' => 'header',
                'title' => __('admin-users.title.users_and_roles'),
            ],
            [
                'title' => __('admin-users.title.users'),
                'icon' => 'ph.bold.user-circle-bold',
                'url' => url('/admin/users'),
            ],
        ];
    }

    public function getPriority(): int
    {
        return 10;
    }
}
