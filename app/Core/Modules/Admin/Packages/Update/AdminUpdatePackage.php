<?php

namespace Flute\Admin\Packages\Update;

use Flute\Admin\Support\AbstractAdminPackage;

class AdminUpdatePackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize() : void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');

        $this->loadViews('Resources/views', 'admin-update');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/sass/update.scss');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions() : array
    {
        return ['admin', 'admin.update'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems() : array
    {
        $badge = $this->getBadge();

        return [
            [
                'title' => __('admin-update.title'),
                'icon' => 'ph.bold.arrows-clockwise-bold',
                'url' => url('/admin/update'),
                'badge' => $badge['text'] ?? null,
                'badge-type' => $badge['class'] ?? null,
            ],
        ];
    }

    protected function getBadge() : array
    {
        $updateService = app(\Flute\Core\Update\Services\UpdateService::class);
        $updates = $updateService->getAvailableUpdates();
        $count = 0;

        if (!empty($updates['cms'])) {
            $count++;
        }
        if (!empty($updates['modules'])) {
            $count += count($updates['modules']);
        }
        if (!empty($updates['themes'])) {
            $count += count($updates['themes']);
        }

        return $count > 0 ? [
            'text' => $count,
            'class' => 'accent',
        ] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority() : int
    {
        return 14;
    }
}