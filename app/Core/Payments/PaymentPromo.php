<?php

namespace Flute\Core\Payments;

use Flute\Core\Database\Entities\PromoCode;
use Flute\Core\Database\Entities\PromoCodeUsage;
use Flute\Core\Payments\Exceptions\PaymentPromoException;

class PaymentPromo
{
    /**
     * Validates a promo code for a specific user.
     *
     * @param ?string $code Promo code to validate.
     * @param int $userId User ID for whom the promo code is being validated.
     * @param int $amount
     * 
     * @return array
     * 
     * @throws PaymentPromoException
     */
    public function validate(?string $code, int $userId = 0): array
    {
        if (empty($code)) {
            throw new PaymentPromoException(__('lk.promo_is_empty'));
        }

        $promoCode = $this->get($code);

        if ($promoCode === null) {
            throw new PaymentPromoException(__('lk.promo_not_found'));
        }

        if ($promoCode->usages->count() >= $promoCode->max_usages) {
            throw new PaymentPromoException(__('lk.promo_limit'));
        }

        // Check if promo code is expired.
        $currentDate = new \DateTime();
        if ($promoCode->expires_at <= $currentDate) {
            throw new PaymentPromoException(__('lk.promo_expired'));
        }

        // Check if the user has already used this promo code.
        $repository = rep(PromoCodeUsage::class);
        $usage = $repository->findOne(['promoCode_id' => $promoCode->id, 'user_id' => $userId === 0 ? user()->getCurrentUser()->id : $userId]);

        if ($usage !== null) {
            throw new PaymentPromoException(__('lk.promo_used'));
        }

        return [
            'message' => $this->getPromoMessage($promoCode),
            'type' => $promoCode->type,
            'value' => $promoCode->value
        ];
    }

    /**
     * Get promo message
     * 
     * @param PromoCode $promoCode
     * 
     * @return string
     * 
     * @throws PaymentPromoException
     */
    protected function getPromoMessage(PromoCode $promoCode)
    {
        switch ($promoCode->type) {
            case 'amount':
                $message = __('lk.promo_amount', [':value' => $promoCode->value, ':currency' => config('lk.currency_view')]);
                break;
            case 'percentage':
                $message = __('lk.promo_percentage', [':percentage' => $promoCode->value]);
                break;
            case 'subtract':
                $message = __('lk.promo_subtract', [':value' => $promoCode->value, ':currency' => config('lk.currency_view')]);
                break;
            default:
                throw new PaymentPromoException(__('lk.promo_invalid_type'));
        }

        return $message;
    }

    /**
     * Retrieves a promo code entity.
     *
     * @param string $code Promo code to retrieve.
     * @return PromoCode|null Returns the PromoCode entity or null if not found.
     */
    public function get(string $code): ?PromoCode
    {
        $repository = rep(PromoCode::class);
        return $repository->findOne(['code' => $code]);
    }

    /**
     * Exists the promo code
     * 
     * @return bool
     */
    public function exists(string $code): bool
    {
        return !empty(rep(PromoCode::class)->findOne(['code' => $code]));
    }

    /**
     * Creates a new promo code.
     *
     * @param string $code Promo code to create.
     * @param string $type Type of the promo (amount, percentage, subtract).
     * @param float $value Value of the promo.
     * @param \DateTime $expires_at Expiration date of the promo code.
     * @return PromoCode Returns the newly created PromoCode entity.
     */
    public function create(string $code, string $type, float $value, \DateTime $expires_at): PromoCode
    {
        $promoCode = new PromoCode();
        $promoCode->code = $code;
        $promoCode->type = $type;
        $promoCode->value = $value;
        $promoCode->expires_at = $expires_at;

        transaction($promoCode)->run();

        return $promoCode;
    }

    /**
     * Deletes a promo code.
     *
     * @param string $code Promo code to delete.
     * @return void
     */
    public function delete(string $code): void
    {
        $promoCode = $this->get($code);

        if ($promoCode !== null) {
            transaction($promoCode, 'delete')->run();
        }
    }
}