<?php

namespace Flute\Core\Modules\Page\Widgets;

use Cycle\Database\Injection\Parameter;
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
        $paymentIds = cache()->callback('flute.widget.recent_payments', static function () {
            $payments = PaymentInvoice::query()
                ->where('isPaid', true)
                ->orderBy('paidAt', 'DESC')
                ->limit(5)
                ->fetchAll();

            return array_map(static fn ($p) => $p->id, $payments);
        }, self::CACHE_TIME);

        $recentPayments = !empty($paymentIds)
            ? PaymentInvoice::query()
                ->where('id', 'IN', new Parameter($paymentIds))
                ->orderBy('paidAt', 'DESC')
                ->load(['user', 'currency'])
                ->fetchAll()
            : [];

        return view('flute::widgets.recent-payments', ['payments' => $recentPayments])->render();
    }

    public function getCategory(): string
    {
        return 'payments';
    }

    public function getDefaultWidth(): int
    {
        return 4;
    }
}
