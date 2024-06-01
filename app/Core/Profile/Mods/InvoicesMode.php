<?php

namespace Flute\Core\Profile\Mods;

use Flute\Core\Contracts\ProfileModInterface;
use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Database\Entities\User;
use Flute\Core\Table\TableColumn;

class InvoicesMode implements ProfileModInterface
{
    public function getKey() : string
    {
        return 'invoices';
    }

    public function render(User $user): string
    {
        $table = table();

        $invoices = rep(PaymentInvoice::class)->select()->load(['user', 'promoCode', 'currency'])->where([
            'user_id' => $user->id
        ])->fetchAll();

        foreach ($invoices as $item) {
            $item->amountWithCurrency = $item->originalAmount . ' ' . $item->currency->code;
            $item->promoCode = !empty($item->promoCode) ? $item->promoCode->code : __('def.no');

            if ($item->isPaid) {
                $item->paidCard = '<div class="paid-container">
                    <span class="paid-status paid">' . __('def.paid') . '</span>
                    <small class="paid-at">' . __('admin.payments.paid_at', [
                        ':time' => $item->paidAt->format(default_date_format())
                    ]) . '</small>
                </div>';
            } else {
                $item->paidCard = '<span class="paid-status notpaid">' . __('def.not_paid') . '</span>';
            }
        }

        // Добавляем объединенную колонку
        $table->addColumn(new TableColumn('id', 'ID'));
        $table->addColumn(new TableColumn('gateway', __('admin.payments.adapter')));
        $table->addColumn(new TableColumn('transactionId', __('admin.payments.transactionId')));
        $table->addColumn(new TableColumn('amountWithCurrency', __('admin.payments.amount')));
        $table->addColumn(new TableColumn('promoCode', __('admin.payments.promoCode')));
        $table->addColumn((new TableColumn('paidCard', __('admin.payments.isPaid')))->setClean(false));

        $table->setData($invoices);

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