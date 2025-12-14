<?php

namespace Flute\Core\Modules\Page\Widgets;

use function count;

use Cycle\Database\Injection\Parameter;
use DateTimeImmutable;
use Flute\Core\Database\Entities\PromoCode;

class ActivePromoCodesWidget extends AbstractWidget
{
    protected const CACHE_TIME = 300;

    public function getName(): string
    {
        return 'widgets.active_promo_codes';
    }

    public function getIcon(): string
    {
        return 'ph.regular.ticket';
    }

    public function render(array $settings): string
    {
        $currentUser = user()->getCurrentUser();
        $userId = $currentUser?->id ?? 0;
        $userRoleIds = $currentUser ? array_map(static fn ($role) => $role->id, $currentUser->roles) : [];

        $promoData = cache()->callback('flute.widget.promo_codes', static function () {
            $now = new DateTimeImmutable();

            $promoCodes = PromoCode::query()
                ->load('roles')
                ->load('usages')
                ->where('expires_at', '>', $now)
                ->orWhere('expires_at', null)
                ->fetchAll();

            return array_map(static fn ($code) => [
                'id' => $code->id,
                'expires_at' => $code->expires_at?->getTimestamp(),
                'max_usages' => $code->max_usages,
                'max_uses_per_user' => $code->max_uses_per_user,
                'usage_count' => count($code->usages),
                'role_ids' => array_map(static fn ($r) => $r->id, $code->roles),
                'user_usage_map' => array_count_values(array_map(static fn ($u) => $u->user_id, $code->usages)),
            ], $promoCodes);
        }, self::CACHE_TIME);

        $validPromoIds = [];
        $now = time();

        foreach ($promoData as $data) {
            if ($data['expires_at'] !== null && $data['expires_at'] < $now) {
                continue;
            }

            if ($data['max_usages'] !== null && $data['usage_count'] >= $data['max_usages']) {
                continue;
            }

            if (!empty($data['role_ids']) && $currentUser) {
                if (empty(array_intersect($userRoleIds, $data['role_ids']))) {
                    continue;
                }
            }

            if ($currentUser) {
                $userUsageCount = $data['user_usage_map'][$userId] ?? 0;
                if ($data['max_uses_per_user'] !== null && $userUsageCount >= $data['max_uses_per_user']) {
                    continue;
                }
                if ($data['max_uses_per_user'] === null && $userUsageCount > 0) {
                    continue;
                }
            }

            $validPromoIds[] = $data['id'];
        }

        $promoCodes = !empty($validPromoIds)
            ? PromoCode::query()->where('id', 'IN', new Parameter($validPromoIds))->fetchAll()
            : [];

        return view('flute::widgets.active-promo-codes', ['promoCodes' => $promoCodes])->render();
    }

    public function getCategory(): string
    {
        return 'payments';
    }

    public function getDefaultWidth(): int
    {
        return 3;
    }
}
