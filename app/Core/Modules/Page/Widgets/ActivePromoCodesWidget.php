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

        $promoCodes = PromoCode::query()
            ->where('expires_at', '>', $now)
            ->orWhere('expires_at', null)
            ->fetchAll();

        $promoCodes = array_filter($promoCodes, static function (PromoCode $code) {
            if ($code->max_usages === null) {
                return true;
            }

            return \count($code->usages) < $code->max_usages;
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