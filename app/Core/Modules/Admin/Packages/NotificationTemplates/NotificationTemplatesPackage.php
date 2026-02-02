<?php

namespace Flute\Admin\Packages\NotificationTemplates;

use Flute\Admin\Support\AbstractAdminPackage;
use Flute\Core\Database\Entities\NotificationTemplate;
use Throwable;

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

        $this->registerScss('Resources/assets/scss/notifications.scss');
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
                'key' => 'notifications',
                'title' => __('admin-notifications.menu.templates'),
                'icon' => 'ph.regular.bell-ringing',
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
        return 18;
    }

    protected function getTemplatesCount(): int
    {
        try {
            return NotificationTemplate::query()->count();
        } catch (Throwable) {
            return 0;
        }
    }
}
