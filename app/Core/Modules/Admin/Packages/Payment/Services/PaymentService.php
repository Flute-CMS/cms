<?php

namespace Flute\Admin\Packages\Payment\Services;

use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Database\Entities\PromoCode;

class PaymentService
{
    /**
     * Получение всех платежных шлюзов.
     */
    public function getAllGateways() : array
    {
        return PaymentGateway::findAll();
    }

    /**
     * Получение платежного шлюза по ID.
     */
    public function getGatewayById(int $id) : ?PaymentGateway
    {
        return PaymentGateway::findByPK($id);
    }

    /**
     * Удаление платежного шлюза.
     */
    public function deleteGateway(PaymentGateway $gateway) : void
    {
        // if ($this->hasActiveInvoices($gateway)) {
        //     throw new \Exception('Невозможно удалить платежный шлюз, так как есть активные счета.');
        // }
        $gateway->delete();
    }

    /**
     * Получение всех промо-кодов.
     */
    public function getAllPromoCodes() : array
    {
        return PromoCode::findAll();
    }

    /**
     * Получение промо-кода по ID.
     */
    public function getPromoCodeById(int $id) : ?PromoCode
    {
        return PromoCode::findByPK($id);
    }

    /**
     * Сохранение промо-кода.
     */
    public function savePromoCode(PromoCode $promoCode, array $data) : void
    {
        $promoCode->code = $data['code'];
        $promoCode->max_usages = (int) $data['max_usages'];
        $promoCode->type = $data['type'];
        $promoCode->value = (float) $data['value'];
        $promoCode->expires_at = $data['expires_at'] ? new \DateTimeImmutable($data['expires_at']) : null;
        $promoCode->save();
    }

    /**
     * Удаление промо-кода.
     */
    public function deletePromoCode(PromoCode $promoCode) : void
    {
        if ($this->hasActiveUsages($promoCode)) {
            throw new \Exception('Невозможно удалить промо-код, так как он уже использовался.');
        }
        $promoCode->delete();
    }

    /**
     * Проверка наличия использований промо-кода.
     */
    private function hasActiveUsages(PromoCode $promoCode) : bool
    {
        return !empty($promoCode->usages);
    }

    /**
     * Получение общей статистики по всем промо-кодам.
     */
    public function getPromoCodeStats(?PromoCode $promoCode = null) : array
    {
        if ($promoCode !== null) {
            return $this->getPromoCodeStatsForSingle($promoCode);
        }

        $allCodes = PromoCode::findAll();
        $now = new \DateTimeImmutable();
        $totalAmount = 0;
        $totalUsages = 0;
        $activeCodes = 0;

        foreach ($allCodes as $code) {
            $stats = $this->getPromoCodeStatsForSingle($code);
            $totalAmount += $stats['total_amount'];
            $totalUsages += $stats['total_usages'];
            
            if (($code->expires_at > $now || $code->expires_at === null) && $stats['remaining_usages'] > 0) {
                $activeCodes++;
            }
        }

        return [
            'total_codes' => count($allCodes),
            'active_codes' => $activeCodes,
            'total_usages' => $totalUsages,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Получение статистики использования промо-кода.
     */
    private function getPromoCodeStatsForSingle(PromoCode $promoCode) : array
    {
        $usages = $promoCode->usages;
        $totalUsages = count($usages);
        $totalAmount = 0;

        foreach ($usages as $usage) {
            if ($usage->invoice->isPaid) {
                $totalAmount += $usage->invoice->amount;
            }
        }

        return [
            'total_usages' => $totalUsages,
            'remaining_usages' => $promoCode->max_usages - $totalUsages,
            'total_amount' => $totalAmount,
            'is_expired' => $promoCode->expires_at !== null && $promoCode->expires_at < new \DateTimeImmutable(),
        ];
    }

    /**
     * Получение истории использования промо-кода.
     */
    public function getPromoCodeUsageHistory(PromoCode $promoCode) : array
    {
        return array_reverse($promoCode->usages);
    }
} 