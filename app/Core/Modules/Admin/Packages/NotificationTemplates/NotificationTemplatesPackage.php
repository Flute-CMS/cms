<?php

namespace Flute\Admin\Packages\NotificationTemplates;

use Flute\Admin\Support\AbstractAdminPackage;
use Flute\Core\Database\Entities\NotificationTemplate;

class NotificationTemplatesPackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');

        $this->loadViews('Resources/views', 'admin-notifications');

        $this->loadTranslations('Resources/lang');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return ['admin', 'admin.notifications'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems(): array
    {
        return [
            [
                'title' => __('admin-notifications.menu.templates'),
                'icon' => 'ph.bold.bell-ringing-bold',
                'url' => url('/admin/notification-templates'),
                'badge' => $this->getTemplatesCount(),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority(): int
    {
        return 15;
    }

    protected function getTemplatesCount(): int
    {
        try {
            return NotificationTemplate::query()->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
