<?php

namespace Flute\Admin\Packages\Modules;

use Flute\Admin\Support\AbstractAdminPackage;
use Flute\Core\ModulesManager\ModuleManager;

class ModulePackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');

        $this->loadViews('Resources/views', 'admin-modules');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/sass/module.scss');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return ['admin', 'admin.modules'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems(): array
    {
        return [
            [
                'key' => 'modules',
                'title' => __('admin-modules.title'),
                'icon' => 'ph.regular.folder',
                'url' => url('/admin/modules'),
                'badge' => $this->getModulesCount(),
            ],
        ];
    }

    public function getPriority(): int
    {
        return 13;
    }

    protected function getModulesCount(): int
    {
        return app(ModuleManager::class)->getModules()->count();
    }
}
