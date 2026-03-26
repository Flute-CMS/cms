<?php

namespace Flute\Admin\Packages\Payment;

use Flute\Admin\Support\AbstractAdminPackage;

class PaymentProvider extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');

        $this->loadViews('Resources/views', 'admin-payment');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/sass/payment.scss');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return ['admin', 'admin.gateways'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems(): array
    {
        return [
            [
                'key' => 'gateways',
                'title' => __('admin-payment.title.gateways'),
                'url' => url('/admin/payment/gateways'),
                'icon' => 'ph.regular.credit-card',
            ],
            [
                'key' => 'invoices',
                'title' => __('admin-payment.title.invoices'),
                'url' => url('/admin/payment/invoices'),
                'icon' => 'ph.regular.receipt',
            ],
            [
                'key' => 'promo-codes',
                'title' => __('admin-payment.title.promo_codes'),
                'url' => url('/admin/payment/promo-codes'),
                'icon' => 'ph.regular.ticket',
            ],
        ];
    }

    public function getPriority(): int
    {
        return 17;
    }
}
