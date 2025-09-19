<?php

namespace Flute\Admin\Packages\Theme;

use Flute\Admin\Support\AbstractAdminPackage;
use Flute\Core\Theme\ThemeManager;

class ThemePackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/sass/theme.scss');

        $this->loadViews('Resources/views', 'admin-theme');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return ['admin', 'admin.themes'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems(): array
    {
        return [
            [
                'title' => __('admin-theme.title.themes'),
                'icon' => 'ph.bold.palette-bold',
                'url' => url('/admin/themes'),
                'badge' => $this->getThemesCount(),
            ],
        ];
    }

    public function getPriority(): int
    {
        return 14;
    }

    protected function getThemesCount(): int
    {
        return sizeof(app(ThemeManager::class)->getAllThemes());
    }
}
