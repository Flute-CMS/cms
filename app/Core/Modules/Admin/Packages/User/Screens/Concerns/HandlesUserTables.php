<?php

namespace Flute\Admin\Packages\User\Screens\Concerns;

use DateTimeZone;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Components\Cells\BadgeLink;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\BalanceHistory;
use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Database\Entities\UserActionLog;
use Flute\Core\Database\Entities\UserBlock;
use Flute\Core\Database\Entities\UserDevice;
use Flute\Core\Database\Entities\UserSocialNetwork;
use Flute\Core\Logging\LogRendererManager;

trait HandlesUserTables
{
    private function sessionsLayout()
    {
        return LayoutFactory::table('userDevices', [
            TD::make('deviceDetails', __('admin-users.table.device'))
                ->render(static fn(UserDevice $device) => $device->deviceDetails)
                ->width('250px'),

            TD::make('ip', __('admin-users.table.ip'))
                ->render(static fn(UserDevice $device) => $device->ip)
                ->width('120px'),

            TD::make('createdAt', __('admin-users.table.first_login'))
                ->render(static function (UserDevice $device) {
                    if (!$device->createdAt) {
                        return '-';
                    }
                    $tz = new DateTimeZone(config('app.timezone', 'UTC'));

                    return $device->createdAt->setTimezone($tz)->format('d.m.Y H:i');
                })
                ->width('150px'),

            TD::make('lastUsedAt', __('admin-users.table.last_login'))
                ->render(static function (UserDevice $device) {
                    if (!$device->lastUsedAt) {
                        return '-';
                    }
                    $tz = new DateTimeZone(config('app.timezone', 'UTC'));

                    return $device->lastUsedAt->setTimezone($tz)->format('d.m.Y H:i');
                })
                ->width('150px')
                ->defaultSort(true, 'desc'),

            TD::make('actions', __('admin-users.table.actions'))
                ->render(fn(UserDevice $device) => $this->deviceActionsDropdown($device))
                ->width('100px'),
        ])
            ->searchable([
                'deviceDetails',
                'ip',
            ])
            ->commands([
                Button::make(__('admin-users.buttons.clear_sessions'))
                    ->type(Color::OUTLINE_DANGER)
                    ->icon('ph.bold.sign-out-bold')
                    ->method('clearUserSessions')
                    ->confirm(__('admin-users.confirms.clear_sessions'))
                    ->fullWidth(),
            ]);
    }

    private function deviceActionsDropdown(UserDevice $device): string
    {
        return DropDown::make()
            ->icon('ph.regular.dots-three-outline-vertical')
            ->list([
                DropDownItem::make(__('admin-users.buttons.terminate_session'))
                    ->confirm(__('admin-users.confirms.terminate_session'))
                    ->method('terminateSession', ['deviceId' => $device->id])
                    ->icon('ph.bold.sign-out-bold')
                    ->type(Color::OUTLINE_DANGER)
                    ->size('small')
                    ->fullWidth(),
            ]);
    }

    private function socialNetworksLayout()
    {
        return LayoutFactory::table('socialNetworks', [
            TD::make('socialNetwork.key', __('admin-users.table.social_network'))
                ->render(static fn(UserSocialNetwork $network) => $network->socialNetwork?->key ?? '-')
                ->width('200px'),

            TD::make('value', __('admin-users.table.value'))
                ->asComponent(BadgeLink::class, [
                    'url' => ':url',
                ])
                ->width('200px'),

            TD::make('name', __('admin-users.table.display_name'))
                ->render(static fn(UserSocialNetwork $network) => $network->name ?? '-')
                ->width('200px'),

            TD::make('linkedAt', __('admin-users.table.link_date'))
                ->render(static fn(UserSocialNetwork $network) => $network->linkedAt->format('d.m.Y H:i'))
                ->width('200px'),

            TD::make('hidden', __('admin-users.table.visibility'))
                ->render(static fn(UserSocialNetwork $network) => view('admin-users::cells.visibility-badge', [
                    'visible' => !$network->hidden,
                ]))
                ->width('100px'),

            TD::make('actions', __('admin-users.table.actions'))
                ->render(fn(UserSocialNetwork $network) => $this->socialNetworkActionsDropdown($network))
                ->width('100px'),
        ])
            ->searchable([
                'socialNetwork.key',
                'value',
                'name',
                'url',
            ])
            ->commands([
                Button::make(__('admin-users.buttons.add_social'))
                    ->type(Color::OUTLINE_PRIMARY)
                    ->icon('ph.bold.plus-bold')
                    ->modal('addSocialNetworkModal')
                    ->fullWidth(),
            ]);
    }

    private function blocksHistoryLayout()
    {
        return LayoutFactory::table('blocksHistory', [
            TD::make('reason', __('admin-users.table.reason'))
                ->render(static fn(UserBlock $block) => $block->reason)
                ->width('300px'),

            TD::make('blockedBy.name', __('admin-users.table.blocked_by'))
                ->render(
                    static fn(UserBlock $block) => (
                        "<a class='badge ghost-primary' href='"
                        . url('admin/users/' . $block->blockedBy->id . '/edit')
                        . "'>{$block->blockedBy->name}</a>"
                    ),
                )
                ->width('200px'),

            TD::make('blockedFrom', __('admin-users.table.blocked_from'))
                ->render(static fn(UserBlock $block) => $block->blockedFrom->format('d.m.Y H:i'))
                ->width('200px'),

            TD::make('blockedUntil', __('admin-users.table.blocked_until'))
                ->render(static fn(UserBlock $block) => $block->blockedUntil
                    ? $block->blockedUntil->format('d.m.Y H:i')
                    : 'Навсегда')
                ->width('200px'),
        ]);
    }

    private function actionHistoryLayout()
    {
        return LayoutFactory::table('actionHistory', [
            TD::make('details', __('admin-users.table.details'))
                ->render(static fn(UserActionLog $log) => app(LogRendererManager::class)->render($log))
                ->width('400px'),
        ]);
    }

    private function depositHistoryLayout()
    {
        return LayoutFactory::table('depositHistory', [
            TD::make('transactionId', __('admin-users.table.transaction_id'))
                ->render(static fn(PaymentInvoice $invoice) => $invoice->transactionId)
                ->width('200px'),

            TD::make('gateway', __('admin-users.table.payment_gateway'))
                ->render(static fn(PaymentInvoice $invoice) => $invoice->gateway)
                ->width('200px'),

            TD::make('amount', __('admin-users.table.amount'))
                ->render(
                    static fn(PaymentInvoice $invoice) => (
                        number_format($invoice->amount, 2)
                        . ' '
                        . $invoice->currency->code
                    ),
                )
                ->width('150px'),

            TD::make('isPaid', __('admin-users.table.status'))
                ->render(static fn(PaymentInvoice $invoice) => view('admin-users::cells.payment-status', [
                    'invoice' => $invoice,
                ]))
                ->width('150px'),

            TD::make('paidAt', __('admin-users.table.payment_date'))
                ->render(static function (PaymentInvoice $invoice) {
                    if (!$invoice->paidAt) {
                        return '-';
                    }

                    $tz = new DateTimeZone(config('app.timezone', 'UTC'));

                    return $invoice->paidAt->setTimezone($tz)->format('d.m.Y H:i');
                })
                ->width('200px'),
        ]);
    }

    private function balanceHistoryLayout()
    {
        return LayoutFactory::table('balanceHistory', [
            TD::make('type', __('admin-users.table.type'))
                ->render(
                    static fn(BalanceHistory $row) => (
                        '<span class="badge ' . match ($row->type) {
                            'topup' => 'success',
                            'purchase' => 'error',
                            'refund' => 'info',
                            'admin' => $row->amount >= 0 ? 'success' : 'warning',
                            default => '',
                        }
                        . '">'
                        . __('profile.edit.balance_history.types.' . $row->type)
                        . '</span>'
                    ),
                )
                ->width('120px'),

            TD::make('amount', __('admin-users.table.amount'))
                ->render(
                    static fn(BalanceHistory $row) => (
                        '<strong style="color:'
                        . ( $row->amount >= 0 ? 'var(--success)' : 'var(--error)' )
                        . '">'
                        . ( $row->amount >= 0 ? '+' : '' )
                        . number_format($row->amount, 2)
                        . ' '
                        . config('lk.currency_view')
                        . '</strong>'
                    ),
                )
                ->width('130px'),

            TD::make('balanceAfter', __('admin-users.table.balance_after'))
                ->render(
                    static fn(BalanceHistory $row) => (
                        number_format($row->balanceAfter, 2)
                        . ' '
                        . config('lk.currency_view')
                    ),
                )
                ->width('130px'),

            TD::make('source', __('admin-users.table.source'))
                ->render(static fn(BalanceHistory $row) => $row->source ?? '-')
                ->width('120px'),

            TD::make('description', __('admin-users.table.description'))->render(
                static fn(BalanceHistory $row) => $row->description ?? '-',
            ),

            TD::make('createdAt', __('admin-users.table.date'))
                ->render(static function (BalanceHistory $row) {
                    $tz = new DateTimeZone(config('app.timezone', 'UTC'));

                    return $row->createdAt->setTimezone($tz)->format('d.m.Y H:i');
                })
                ->width('160px'),
        ]);
    }

    private function socialNetworkActionsDropdown(UserSocialNetwork $network): string
    {
        return DropDown::make()
            ->icon('ph.regular.dots-three-outline-vertical')
            ->list([
                DropDownItem::make(__('admin-users.buttons.edit'))
                    ->modal('editSocialNetworkModal', ['networkId' => $network->id])
                    ->icon('ph.bold.pencil-bold')
                    ->type(Color::OUTLINE_PRIMARY)
                    ->size('small')
                    ->fullWidth(),

                DropDownItem::make($network->hidden ? __('admin-users.buttons.show') : __('admin-users.buttons.hide'))
                    ->method('toggleSocialNetworkVisibility', ['networkId' => $network->id])
                    ->icon($network->hidden ? 'ph.bold.eye-bold' : 'ph.bold.eye-slash-bold')
                    ->type(Color::OUTLINE_WARNING)
                    ->size('small')
                    ->fullWidth(),

                DropDownItem::make(__('admin-users.buttons.delete'))
                    ->confirm(__('admin-users.confirms.delete_social_network'))
                    ->method('deleteSocialNetwork', ['networkId' => $network->id])
                    ->icon('ph.bold.trash-bold')
                    ->type(Color::OUTLINE_DANGER)
                    ->size('small')
                    ->fullWidth(),
            ]);
    }
}
