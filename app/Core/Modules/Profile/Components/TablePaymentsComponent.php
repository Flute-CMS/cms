<?php

namespace Flute\Core\Modules\Profile\Components;

use Carbon\Carbon;
use DateTimeInterface;
use Flute\Core\Components\Table;
use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Database\Entities\PaymentInvoice;

class TablePaymentsComponent extends Table
{
    public $user;

    public int $perPage = 10;

    public array $paginationOptions = [10, 25, 50];

    protected array $gatewayNames = [];

    public function mount()
    {
        $this->user = user()->getCurrentUser();
        $this->loadGatewayNames();

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
                'renderer' => fn (PaymentInvoice $row) => view('flute::components.profile-tabs.edit.payments.transaction-id', [
                    'transactionId' => $row->transactionId,
                    'id' => $row->id,
                    'gateway' => $this->resolveGatewayName($row->gateway),
                ])->render(),
            ],
            [
                'label' => __('profile.edit.payments.table.amount'),
                'field' => 'amount',
                'allowSort' => true,
                'searchable' => false,
                'renderer' => static function (PaymentInvoice $row) {
                    $currencyCode = $row->currency ? $row->currency->code : 'USD';

                    $promoCode = null;
                    $promoValue = null;
                    $promoType = null;

                    if ($row->promoCode) {
                        $promoCode = $row->promoCode->code;
                        $promoValue = $row->promoCode->value;
                        $promoType = $row->promoCode->type;
                    }

                    return view('flute::components.profile-tabs.edit.payments.amount', [
                        'amount' => $row->amount,
                        'currency' => $currencyCode,
                        'originalAmount' => $row->originalAmount,
                        'promoCode' => $promoCode,
                        'promoValue' => $promoValue,
                        'promoType' => $promoType,
                    ])->render();
                },
            ],
            [
                'label' => __('profile.edit.payments.table.status'),
                'field' => 'isPaid',
                'allowSort' => true,
                'searchable' => false,
                'defaultSort' => true,
                'defaultDirection' => 'desc',
                'renderer' => static function (PaymentInvoice $row) {
                    $date = $row->createdAt instanceof DateTimeInterface
                        ? Carbon::instance($row->createdAt)
                        : Carbon::parse($row->createdAt);

                    return view('flute::components.profile-tabs.edit.payments.payment-status', [
                        'isPaid' => $row->isPaid,
                        'paidAt' => $row->paidAt,
                        'date' => $date->format('d.m.Y'),
                        'time' => $date->format('H:i'),
                    ])->render();
                },
            ],
            [
                'label' => '',
                'field' => 'actions',
                'allowSort' => false,
                'searchable' => false,
                'renderer' => static fn (PaymentInvoice $row) => view('flute::components.profile-tabs.edit.payments.pay-button', [
                    'invoice' => $row,
                ])->render(),
            ],
        ];
    }

    protected function loadGatewayNames(): void
    {
        $gateways = PaymentGateway::query()->fetchAll();

        foreach ($gateways as $gw) {
            $this->gatewayNames[$gw->adapter] = $gw->name;
        }
    }

    protected function resolveGatewayName(string $adapter): string
    {
        return $this->gatewayNames[$adapter] ?? $adapter;
    }
}
