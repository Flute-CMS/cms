<?php

namespace Flute\Core\Payments;

use Flute\Core\Database\Entities\PaymentInvoice;
use DateTime;

class PaymentsCleaner
{
    protected const CACHE_KEY = "flute.payments.check_old";
    protected const DAYS = 60;

    public function cleanOldPayments()
    {
        if (!cache()->has(self::CACHE_KEY)) {
            $dateThreshold = new DateTime();
            $dateThreshold->modify('-' . self::DAYS . ' days');

            $oldPayments = rep(PaymentInvoice::class)->findAll([
                'isPaid' => false,
                'created_at' => ['<' => $dateThreshold]
            ]);

            foreach ($oldPayments as $payment) {
                $this->deletePayment($payment);
            }

            cache()->set(self::CACHE_KEY, true);
        }
    }

    protected function deletePayment(PaymentInvoice $payment)
    {
        transaction($payment, 'delete')->run();
    }
}
