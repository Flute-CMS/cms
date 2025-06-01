<?php

namespace Flute\Core\Modules\Page\Widgets;

use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Modules\Page\Widgets\AbstractWidget;

class RecentPaymentsWidget extends AbstractWidget
{
    public function getName() : string
    {
        return 'widgets.recent_payments';
    }

    public function getIcon() : string
    {
        return 'ph.regular.currency-circle-dollar';
    }

    public function render(array $settings) : string
    {
        $recentPayments = PaymentInvoice::query()->where('isPaid', true)->orderBy('paidAt', 'DESC')->limit(5)->fetchAll();

        return view('flute::widgets.recent-payments', ['payments' => $recentPayments])->render();
    }

    public function getCategory() : string
    {
        return 'payments';
    }

    public function getDefaultWidth() : int
    {
        return 4;
    }
}