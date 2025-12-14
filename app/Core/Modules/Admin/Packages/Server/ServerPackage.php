<?php

namespace Flute\Admin\Packages\Server;

use Flute\Admin\Packages\Search\Services\SlashCommandsRegistry;
use Flute\Admin\Packages\Server\Listeners\ServerSearchListener;
use Flute\Admin\Support\AbstractAdminPackage;
use Flute\Core\Database\Entities\Server;

class ServerPackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadViews('Resources/views', 'admin-server');

        $this->loadRoutesFromFile('routes.php');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/sass/server.scss');

        SlashCommandsRegistry::register('server', __('admin-server.search_servers'), 'ph.regular.hard-drives');

        events()->addSubscriber(new ServerSearchListener());
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return ['admin', 'admin.servers'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems(): array
    {
        return [
            [
                'type' => 'header',
                'title' => __('admin-server.title.integrations'),
            ],
            [
                'title' => __('admin-server.title.list'),
                'icon' => 'ph.bold.hard-drives-bold',
                'url' => url('/admin/servers'),
                'badge' => $this->getServersCount(),
            ],
        ];
    }

    public function getPriority(): int
    {
        return 11;
    }

    protected function getServersCount(): int
    {
        return Server::query()->count();
    }
}
