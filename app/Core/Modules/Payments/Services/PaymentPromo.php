<?php

namespace Flute\Core\Modules\Payments\Services;

use DateTime;
use DateTimeImmutable;
use Flute\Core\Database\Entities\PromoCode;
use Flute\Core\Database\Entities\PromoCodeUsage;
use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Payments\Exceptions\PaymentPromoException;

class PaymentPromo
{
    /**
     * Validates a promo code for a specific user.
     *
     * @param ?string $code Promo code to validate.
     * @param int $userId User ID for whom the promo code is being validated.
     * @param float $amount Amount of the purchase
     *
     * @throws PaymentPromoException
     */
    public function validate(?string $code, int $userId = 0, float $amount = 0): array
    {
        if (empty($code)) {
            throw new PaymentPromoException(__('lk.promo_is_empty'));
        }

        $promoCode = $this->get($code);

        if ($promoCode === null) {
            throw new PaymentPromoException(__('lk.promo_not_found'));
        }

        $currentDate = new DateTime();
        if ($promoCode->expires_at !== null && $promoCode->expires_at <= $currentDate) {
            throw new PaymentPromoException(__('lk.promo_expired'));
        }

        if ($promoCode->max_usages !== null && sizeof($promoCode->usages) >= $promoCode->max_usages) {
            throw new PaymentPromoException(__('lk.promo_limit'));
        }

        if ($promoCode->minimum_amount !== null && $amount < $promoCode->minimum_amount) {
            throw new PaymentPromoException(__('lk.promo_minimum_amount', [':amount' => $promoCode->minimum_amount, ':currency' => config('lk.currency_view')]));
        }

        if ($promoCode->type === 'percentage') {
            if ($promoCode->value <= 0 || $promoCode->value > 100) {
                throw new PaymentPromoException(__('lk.promo_invalid_percentage'));
            }
        }

        $currentUserId = $userId === 0 ? user()->getCurrentUser()->id : $userId;

        if (!empty($promoCode->roles)) {
            $currentUser = $userId === 0 ? user()->getCurrentUser() : ($userId ? User::findByPK($userId) : null);
            if ($currentUser) {
                $userRoleIds = array_map(static fn ($role) => $role->id, $currentUser->roles);
                $promoRoleIds = array_map(static fn ($role) => $role->id, $promoCode->roles);
                $hasAllowedRole = !empty(array_intersect($userRoleIds, $promoRoleIds));

                if (!$hasAllowedRole) {
                    throw new PaymentPromoException(__('lk.promo_role_not_allowed'));
                }
            }
        }

        if ($promoCode->max_uses_per_user !== null) {
            $userUsageCount = PromoCodeUsage::query()
                ->where('promoCode_id', $promoCode->id)
                ->where('user_id', $currentUserId)
                ->count();

            if ($userUsageCount >= $promoCode->max_uses_per_user) {
                throw new PaymentPromoException(__('lk.promo_user_limit'));
            }
        } else {
            $usage = PromoCodeUsage::findOne(['promoCode_id' => $promoCode->id, 'user_id' => $currentUserId]);

            if ($usage !== null) {
                throw new PaymentPromoException(__('lk.promo_used'));
            }
        }

        return [
            'message' => $this->getPromoMessage($promoCode),
            'type' => $promoCode->type,
            'value' => $promoCode->value,
        ];
    }

    /**
     * Retrieves a promo code entity.
     *
     * @param string $code Promo code to retrieve.
     * @return PromoCode|null Returns the PromoCode entity or null if not found.
     */
    public function get(string $code): ?PromoCode
    {
        return PromoCode::findOne(['code' => $code]);
    }

    /**
     * Exists the promo code
     */
    public function exists(string $code): bool
    {
        return !empty(PromoCode::findOne(['code' => $code]));
    }

    /**
     * Creates a new promo code.
     *
     * @param string $code Promo code to create.
     * @param string $type Type of the promo (amount, percentage).
     * @param float $value Value of the promo.
     * @param DateTimeImmutable  $expires_at Expiration date of the promo code.
     * @return PromoCode Returns the newly created PromoCode entity.
     */
    public function create(string $code, string $type, float $value, DateTimeImmutable $expires_at): PromoCode
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
     */
    public function delete(string $code): void
    {
        $promoCode = $this->get($code);

        if ($promoCode !== null) {
            transaction($promoCode, 'delete')->run();
        }
    }

    /**
     * Get promo message
     *
     * @throws PaymentPromoException
     * @return string
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
            default:
                throw new PaymentPromoException(__('lk.promo_invalid_type'));
        }

        return $message;
    }
}
