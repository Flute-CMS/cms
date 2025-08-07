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
                'type' => 'header',
                'title' => __('admin-payment.title.finance'),
            ],
            [
                'title' => __('admin-payment.title.payment_system'),
                'icon' => 'ph.bold.wallet-bold',
                'permission' => 'admin.gateways',
                'children' => [
                    [
                        'title' => __('admin-payment.title.gateways'),
                        'url' => url('/admin/payment/gateways'),
                        'icon' => 'ph.bold.money-wavy-bold',
                    ],
                    [
                        'title' => __('admin-payment.title.invoices'),
                        'url' => url('/admin/payment/invoices'),
                        'icon' => 'ph.bold.credit-card-bold',
                    ],
                    [
                        'title' => __('admin-payment.title.promo_codes'),
                        'url' => url('/admin/payment/promo-codes'),
                        'icon' => 'ph.bold.tag-bold',
                    ],
                ],
            ],
        ];
    }

    public function getPriority(): int
    {
        return 17;
    }
}
