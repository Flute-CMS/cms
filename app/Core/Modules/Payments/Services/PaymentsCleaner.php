<?php

namespace Flute\Core\Modules\Payments\Services;

use DateTime;
use Flute\Core\Database\Entities\PaymentInvoice;

class PaymentsCleaner
{
    protected const CACHE_KEY = "flute.payments.check_old";

    protected const DAYS = 60;

    public function cleanOldPayments()
    {
        if (!cache()->has(self::CACHE_KEY)) {
            $dateThreshold = new DateTime();
            $dateThreshold->modify('-' . self::DAYS . ' days');

            $oldPayments = PaymentInvoice::findAll([
                'isPaid' => false,
                'created_at' => ['<' => $dateThreshold],
            ]);

            foreach ($oldPayments as $payment) {
                $payment->delete();
            }

            cache()->set(self::CACHE_KEY, true, 43200);
        }
    }
}
