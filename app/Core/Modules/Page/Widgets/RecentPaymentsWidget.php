<?php

namespace Flute\Core\Modules\Page\Widgets;

use Flute\Core\Database\Entities\PaymentInvoice;

class RecentPaymentsWidget extends AbstractWidget
{
    protected const CACHE_TIME = 60;

    public function getName(): string
    {
        return 'widgets.recent_payments';
    }

    public function getIcon(): string
    {
        return 'ph.regular.currency-circle-dollar';
    }

    public function render(array $settings): string
    {
        $recentPayments = cache()->callback(
            'flute.widget.recent_payments',
            static function () {
                return PaymentInvoice::query()
                    ->where('isPaid', true)
                    ->orderBy('paidAt', 'DESC')
                    ->limit(5)
                    ->load(['user', 'currency'])
                    ->fetchAll();
            },
            self::CACHE_TIME,
        );

        return view('flute::widgets.recent-payments', ['payments' => $recentPayments])->render();
    }

    public function getCategory(): string
    {
        return 'payments';
    }

    public function getDescription(): string
    {
        return 'widgets.recent_payments_desc';
    }

    public function getCacheTime(): int
    {
        return self::CACHE_TIME;
    }

    public function getDefaultWidth(): int
    {
        return 4;
    }
}
