<?php

namespace Flute\Core\Profile\Mods;

use Flute\Core\Contracts\ProfileModInterface;
use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Database\Entities\User;

class InvoicesMode implements ProfileModInterface
{
    public function getKey() : string
    {
        return 'invoices';
    }

    public function render(User $user): string
    {
        $table = table();

        $invoices = rep(PaymentInvoice::class)->findAll([
            'user_id' => $user->id
        ]);

        foreach( $invoices as $key => $val ) {
            $val->isPaid = ((bool) $val->isPaid) ? __('def.paid') : __('def.not_paid');
        }

        $table->fromEntity($invoices, ['user', 'promoCode', 'currency', 'amount']);

        return render('pages/profile/edit/invoices', [
            "table" => $table->render()
        ], true);
    }

    public function getSidebarInfo() : array
    {
        return [
            'icon' => 'ph ph-money-wavy',
            'name' => 'profile.settings.invoices',
            'desc' => 'profile.settings.invoices_desc',
        ];
    }
}