<?php

namespace Flute\Admin\Packages\Currency;

use Flute\Admin\Packages\Currency\Services\CurrencyRateService;
use Flute\Admin\Support\AbstractAdminPackage;

class CurrencyPackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/sass/currency.scss');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        if (config('app.cron_mode')) {
            scheduler()->call(static function (): void {
                ( new CurrencyRateService() )->updateAutoRates();
            })->hourly();
        } else {
            cache()->callback(
                'flute.currency_rates.cron',
                static function () {
                    ( new CurrencyRateService() )->updateAutoRates();

                    return true;
                },
                3600,
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return ['admin', 'admin.currency'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems(): array
    {
        return [
            [
                'key' => 'currencies',
                'title' => __('admin-currency.title.list'),
                'icon' => 'ph.regular.money',
                'url' => url('/admin/currency'),
            ],
        ];
    }

    public function getPriority(): int
    {
        return 18;
    }
}
