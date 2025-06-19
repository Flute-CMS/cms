<?php

namespace Flute\Core\Modules\Page\Widgets;

use Flute\Core\Database\Entities\PromoCode;
use Flute\Core\Modules\Page\Widgets\AbstractWidget;

class ActivePromoCodesWidget extends AbstractWidget
{
    public function getName() : string
    {
        return 'widgets.active_promo_codes';
    }

    public function getIcon() : string
    {
        return 'ph.regular.ticket';
    }

    public function render(array $settings) : string
    {
        $now = new \DateTimeImmutable();
        $currentUser = user()->getCurrentUser();

        $promoCodes = PromoCode::query()
            ->where('expires_at', '>', $now)
            ->orWhere('expires_at', null)
            ->fetchAll();

        $promoCodes = array_filter($promoCodes, static function (PromoCode $code) use ($currentUser) {
            if ($code->expires_at !== null && $code->expires_at < new \DateTimeImmutable()) {
                return false;
            }

            if ($code->max_usages !== null && \count($code->usages) >= $code->max_usages) {
                return false;
            }

            if (!empty($code->roles) && $currentUser) {
                $userRoleIds = array_map(fn($role) => $role->id, $currentUser->roles);
                $promoRoleIds = array_map(fn($role) => $role->id, $code->roles);
                if (empty(array_intersect($userRoleIds, $promoRoleIds))) {
                    return false;
                }
            }

            if ($currentUser && $code->max_uses_per_user !== null) {
                $userUsageCount = 0;
                foreach ($code->usages as $usage) {
                    if ($usage->user_id === $currentUser->id) {
                        $userUsageCount++;
                    }
                }
                if ($userUsageCount >= $code->max_uses_per_user) {
                    return false;
                }
            } elseif ($currentUser) {
                foreach ($code->usages as $usage) {
                    if ($usage->user_id === $currentUser->id) {
                        return false;
                    }
                }
            }

            return true;
        });

        return view('flute::widgets.active-promo-codes', ['promoCodes' => $promoCodes])->render();
    }

    public function getCategory() : string
    {
        return 'payments';
    }

    public function getDefaultWidth() : int
    {
        return 3;
    }
}