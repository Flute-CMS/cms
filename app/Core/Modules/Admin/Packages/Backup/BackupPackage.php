<?php

namespace Flute\Admin\Packages\Backup;

use Flute\Admin\Support\AbstractAdminPackage;

class BackupPackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');

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
                'key' => 'backups',
                'title' => __('admin-backup.title'),
                'icon' => 'ph.regular.cloud-arrow-down',
                'url' => url('/admin/backups'),
            ],
        ];
    }

    public function getPriority(): int
    {
        return 16;
    }
}
