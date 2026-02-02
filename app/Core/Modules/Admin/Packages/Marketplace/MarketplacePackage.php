<?php

namespace Flute\Admin\Packages\Marketplace;

use Flute\Admin\Support\AbstractAdminPackage;

class MarketplacePackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadViews('Resources/views', 'admin-marketplace');

        $this->loadRoutesFromFile('routes.php');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/scss/marketplace.scss');
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
                'key' => 'marketplace',
                'title' => __('admin-marketplace.labels.marketplace'),
                'icon' => 'ph.regular.storefront',
                'url' => url('/admin/marketplace'),
            ],
        ];
    }

    public function getPriority(): int
    {
        return 15;
    }
}
