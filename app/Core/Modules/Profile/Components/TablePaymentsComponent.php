<?php

namespace Flute\Core\Modules\Profile\Components;

use Carbon\Carbon;
use Flute\Core\Components\Table;
use Flute\Core\Database\Entities\PaymentInvoice;

class TablePaymentsComponent extends Table
{
    public $user;

    public int $perPage = 10;

    public array $paginationOptions = [10, 25, 50];

    public function mount()
    {
        $this->user = user()->getCurrentUser();

        $this->columns = $this->getColumns();

        $query = PaymentInvoice::query()
            ->load(['currency'])
            ->where('user_id', '=', (string) $this->user->id);

        $this->setSelect($query);

        parent::mount();
    }

    public function getColumns(): array
    {
        return [
            [
                'label' => __('profile.edit.payments.table.transaction'),
                'field' => 'transactionId',
                'allowSort' => true,
                'searchable' => true,
                'renderer' => function (PaymentInvoice $row) {
                    return view('flute::components.profile-tabs.edit.payments.transaction-id', [
                        'transactionId' => $row->transactionId,
                        'id' => $row->id,
                    ])->render();
                },
            ],
            [
                'label' => __('profile.edit.payments.table.gateway'),
                'field' => 'gateway',
                'allowSort' => true,
                'searchable' => true,
                'renderer' => function (PaymentInvoice $row) {
                    return view('flute::components.profile-tabs.edit.payments.payment-gateway', [
                        'gateway' => $row->gateway,
                    ])->render();
                },
            ],
            [
                'label' => __('profile.edit.payments.table.amount'),
                'field' => 'amount',
                'allowSort' => true,
                'searchable' => false,
                'renderer' => function (PaymentInvoice $row) {
                    $currencyCode = $row->currency ? $row->currency->code : 'USD';

                    return view('flute::components.profile-tabs.edit.payments.amount', [
                        'amount' => $row->amount,
                        'currency' => $currencyCode,
                        'originalAmount' => $row->originalAmount,
                    ])->render();
                },
            ],
            [
                'label' => __('profile.edit.payments.table.status'),
                'field' => 'isPaid',
                'allowSort' => true,
                'searchable' => false,
                'renderer' => function (PaymentInvoice $row) {
                    return view('flute::components.profile-tabs.edit.payments.payment-status', [
                        'isPaid' => $row->isPaid,
                        'paidAt' => $row->paidAt,
                    ])->render();
                },
            ],
            [
                'label' => __('profile.edit.payments.table.promo'),
                'field' => 'promoCode',
                'allowSort' => false,
                'searchable' => false,
                'renderer' => function (PaymentInvoice $row) {
                    if (!$row->promoCode) {
                        return '-';
                    }

                    return view('flute::components.profile-tabs.edit.payments.promo-code', [
                        'promoCode' => $row->promoCode->code,
                        'value' => $row->promoCode->value,
                        'type' => $row->promoCode->type,
                    ])->render();
                },
            ],
            [
                'label' => __('profile.edit.payments.table.date'),
                'field' => 'createdAt',
                'allowSort' => true,
                'searchable' => false,
                'defaultSort' => true,
                'defaultDirection' => 'desc',
                'renderer' => function (PaymentInvoice $row) {
                    $date = $row->createdAt instanceof \DateTimeInterface
                        ? Carbon::instance($row->createdAt)
                        : Carbon::parse($row->createdAt);

                    return view('flute::components.profile-tabs.edit.payments.date', [
                        'date' => $date->format('d.m.Y H:i'),
                        'timestamp' => $date->timestamp,
                    ])->render();
                },
            ],
        ];
    }
}
