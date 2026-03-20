<?php

namespace Flute\Core\Modules\Payments\Services;

use DateTime;
use Flute\Core\Database\Entities\PaymentInvoice;

class PaymentsCleaner
{
    protected const CACHE_KEY = 'flute.payments.check_old';

    protected const DAYS = 60;

    public function cleanOldPayments()
    {
        if (!cache()->has(self::CACHE_KEY)) {
            $dateThreshold = new DateTime();
            $dateThreshold->modify('-' . self::DAYS . ' days');

            $batchSize = 500;
            do {
                $oldPayments = PaymentInvoice::query()
                    ->where('isPaid', false)
                    ->where('created_at', '<', $dateThreshold)
                    ->limit($batchSize)
                    ->fetchAll();

                if (!empty($oldPayments)) {
                    $em = new \Cycle\ORM\EntityManager(orm());
                    foreach ($oldPayments as $payment) {
                        $em->delete($payment);
                    }
                    $em->run();
                }
            } while (count($oldPayments) === $batchSize);

            cache()->set(self::CACHE_KEY, true, 43200);
        }
    }
}
