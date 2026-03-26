<?php

namespace Flute\Core\Modules\Profile\Components;

use Carbon\Carbon;
use DateTimeInterface;
use Flute\Core\Components\Table;
use Flute\Core\Database\Entities\BalanceHistory;

class TableBalanceHistoryComponent extends Table
{
    public $user;

    public int $perPage = 10;

    public array $paginationOptions = [10, 25, 50];

    public function mount()
    {
        $this->user = user()->getCurrentUser();

        $this->columns = $this->getColumns();

        $query = BalanceHistory::query()->where('user_id', '=', (string) $this->user->id);

        $this->setSelect($query);

        parent::mount();
    }

    public function getColumns(): array
    {
        return [
            [
                'label' => __('profile.edit.balance_history.table.type'),
                'field' => 'type',
                'allowSort' => true,
                'searchable' => false,
                'renderer' =>
                    static fn(BalanceHistory $row) => view('flute::components.profile-tabs.edit.balance-history.type-badge', [
                        'type' => $row->type,
                        'amount' => $row->amount,
                    ])->render(),
            ],
            [
                'label' => __('profile.edit.balance_history.table.description'),
                'field' => 'description',
                'allowSort' => false,
                'searchable' => true,
                'renderer' =>
                    static fn(BalanceHistory $row) => view('flute::components.profile-tabs.edit.balance-history.description-cell', [
                        'description' => $row->description,
                        'source' => $row->source,
                    ])->render(),
            ],
            [
                'label' => __('profile.edit.balance_history.table.amount'),
                'field' => 'amount',
                'allowSort' => true,
                'searchable' => false,
                'renderer' =>
                    static fn(BalanceHistory $row) => view('flute::components.profile-tabs.edit.balance-history.amount-cell', [
                        'amount' => $row->amount,
                        'balanceAfter' => $row->balanceAfter,
                        'currency' => config('lk.currency_view'),
                    ])->render(),
            ],
            [
                'label' => __('profile.edit.balance_history.table.date'),
                'field' => 'createdAt',
                'allowSort' => true,
                'searchable' => false,
                'defaultSort' => true,
                'defaultDirection' => 'desc',
                'renderer' => static function (BalanceHistory $row) {
                    $date = $row->createdAt instanceof DateTimeInterface
                        ? Carbon::instance($row->createdAt)
                        : Carbon::parse($row->createdAt);

                    return view('flute::components.profile-tabs.edit.balance-history.date-cell', [
                        'date' => $date->format('d.m.Y'),
                        'time' => $date->format('H:i'),
                    ])->render();
                },
            ],
        ];
    }
}
