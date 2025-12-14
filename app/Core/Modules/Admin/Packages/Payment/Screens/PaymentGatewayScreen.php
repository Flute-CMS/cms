<?php

namespace Flute\Admin\Packages\Payment\Screens;

use Carbon\Carbon;
use Exception;
use Flute\Admin\Packages\Payment\Services\PaymentService;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Database\Entities\PaymentInvoice;

class PaymentGatewayScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin.payments';

    public $gateways;

    public $metrics;

    private PaymentService $paymentService;

    public function mount(): void
    {
        $this->paymentService = app(PaymentService::class);
        $this->gateways = rep(PaymentGateway::class)->select();
        $this->metrics = $this->calculateMetrics();

        $this->name = __('admin-payment.title.gateways');
        $this->description = __('admin-payment.title.gateways_description');

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-payment.title.gateways'));
    }

    /**
     * Командная панель.
     */
    public function commandBar(): array
    {
        return [
            Button::make(__('admin-payment.buttons.add_gateway'))
                ->type(Color::PRIMARY)
                ->icon('ph.bold.plus-bold')
                ->redirect(url('/admin/payment/gateways/add')),
        ];
    }

    /**
     * Определение макета экрана.
     */
    public function layout(): array
    {
        return [
            LayoutFactory::metrics([
                __('admin-payment.metrics.total_gateways') => 'metrics.total_gateways',
                __('admin-payment.metrics.active_gateways') => 'metrics.active_gateways',
                __('admin-payment.metrics.today_transactions') => 'metrics.today_transactions',
                __('admin-payment.metrics.today_revenue') => 'metrics.today_revenue',
            ])->setIcons([
                __('admin-payment.metrics.total_gateways') => 'bank',
                __('admin-payment.metrics.active_gateways') => 'check-circle',
                __('admin-payment.metrics.today_transactions') => 'chart-line-up',
                __('admin-payment.metrics.today_revenue') => 'money',
            ]),

            LayoutFactory::table('gateways', [
                TD::selection('id'),
                TD::make('image', '')
                    ->render(static fn (PaymentGateway $gateway) => view('admin-payment::cells.gateway-image', ['gateway' => $gateway]))
                    ->width('80px'),

                TD::make('name', __('admin-payment.table.name'))
                    ->render(static fn (PaymentGateway $gateway) => $gateway->name)
                    ->width('150px'),

                TD::make('adapter', __('admin-payment.table.adapter'))
                    ->width('200px'),

                TD::make('enabled', __('admin-payment.table.status'))
                    ->render(static fn (PaymentGateway $gateway) => view('admin-payment::cells.gateway-status', ['enabled' => $gateway->enabled]))
                    ->width('150px'),

                TD::make('createdAt', __('admin-payment.table.created_at'))
                    ->sort()
                    ->render(static fn (PaymentGateway $gateway) => $gateway->createdAt->format(default_date_format()))
                    ->width('200px'),

                TD::make('actions', __('admin-payment.table.actions'))
                    ->class('actions-col')
                    ->render(fn (PaymentGateway $gateway) => $this->gatewayActionsDropdown($gateway))
                    ->width('100px'),
            ])
                ->searchable([
                    'name',
                    'adapter',
                ])
                ->bulkActions([
                    Button::make(__('admin.bulk.enable_selected'))
                        ->icon('ph.bold.play-bold')
                        ->type(Color::OUTLINE_SUCCESS)
                        ->method('bulkEnableGateways'),

                    Button::make(__('admin.bulk.disable_selected'))
                        ->icon('ph.bold.power-bold')
                        ->type(Color::OUTLINE_WARNING)
                        ->method('bulkDisableGateways'),

                    Button::make(__('admin.bulk.delete_selected'))
                        ->icon('ph.bold.trash-bold')
                        ->type(Color::OUTLINE_DANGER)
                        ->confirm(__('admin.confirms.delete_selected'))
                        ->method('bulkDeleteGateways'),
                ]),
        ];
    }

    /**
     * Переключение статуса шлюза.
     */
    public function toggleGateway()
    {
        try {
            $gatewayId = intval(request()->input('gatewayId'));

            $gateway = $this->paymentService->getGatewayById($gatewayId);

            if (!$gateway) {
                $this->flashMessage(__('admin-payment.messages.gateway_not_found'), 'error');

                return;
            }

            $gateway->enabled = !$gateway->enabled;
            $gateway->saveOrFail();

            $this->flashMessage(
                $gateway->enabled ? __('admin-payment.messages.gateway_enabled') : __('admin-payment.messages.gateway_disabled'),
                'success'
            );

            $this->metrics = $this->calculateMetrics();
        } catch (Exception $e) {
            $this->flashMessage(__('admin-payment.messages.status_change_error', ['message' => $e->getMessage()]), 'error');
        }
    }

    /**
     * Удаление платежного шлюза.
     */
    public function deleteGateway()
    {
        $gatewayId = request()->input('gatewayId');
        $gateway = $this->paymentService->getGatewayById($gatewayId);

        if (!$gateway) {
            $this->flashMessage(__('admin-payment.messages.gateway_not_found'), 'error');

            return;
        }

        try {
            $this->paymentService->deleteGateway($gateway);
            $this->flashMessage(__('admin-payment.messages.gateway_deleted'), 'success');

            $this->gateways = rep(PaymentGateway::class)->findAll();
            $this->metrics = $this->calculateMetrics();
        } catch (Exception $e) {
            $this->flashMessage(__('admin-payment.messages.delete_error', ['message' => $e->getMessage()]), 'error');
        }
    }

    public function bulkDeleteGateways(): void
    {
        $ids = request()->input('selected', []);
        if (!$ids) {
            return;
        }
        foreach ($ids as $id) {
            $gateway = $this->paymentService->getGatewayById((int) $id);
            if (!$gateway) {
                continue;
            }

            try {
                $this->paymentService->deleteGateway($gateway);
            } catch (Exception $e) {
                // continue
            }
        }
        $this->gateways = rep(PaymentGateway::class)->findAll();
        $this->metrics = $this->calculateMetrics();
        $this->flashMessage(__('admin-payment.messages.gateway_deleted'), 'success');
    }

    public function bulkEnableGateways(): void
    {
        $ids = request()->input('selected', []);
        if (!$ids) {
            return;
        }
        foreach ($ids as $id) {
            $gateway = $this->paymentService->getGatewayById((int) $id);
            if (!$gateway) {
                continue;
            }

            try {
                $gateway->enabled = true;
                $gateway->saveOrFail();
            } catch (Exception $e) {
                // continue
            }
        }
        $this->gateways = rep(PaymentGateway::class)->findAll();
        $this->metrics = $this->calculateMetrics();
        $this->flashMessage(__('admin-payment.messages.gateway_enabled'), 'success');
    }

    public function bulkDisableGateways(): void
    {
        $ids = request()->input('selected', []);
        if (!$ids) {
            return;
        }
        foreach ($ids as $id) {
            $gateway = $this->paymentService->getGatewayById((int) $id);
            if (!$gateway) {
                continue;
            }

            try {
                $gateway->enabled = false;
                $gateway->saveOrFail();
            } catch (Exception $e) {
                // continue
            }
        }
        $this->gateways = rep(PaymentGateway::class)->findAll();
        $this->metrics = $this->calculateMetrics();
        $this->flashMessage(__('admin-payment.messages.gateway_disabled'), 'warning');
    }

    /**
     * Calculate metrics for the payment gateways dashboard
     */
    private function calculateMetrics(): array
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $yesterday = $today->copy()->subDay();
        $lastMonth = $today->copy()->subDays(30);

        $gateways = $this->gateways;
        $totalGateways = count($gateways);
        $activeGateways = 0;
        $todayTransactions = 0;
        $todayRevenue = 0;
        $totalTransactions = 0;
        $totalRevenue = 0;

        $yesterdayTransactions = 0;
        $yesterdayRevenue = 0;
        $lastMonthGateways = 0;

        foreach ($gateways as $gateway) {
            if ($gateway->enabled) {
                $activeGateways++;
            }

            if ($gateway->createdAt <= $lastMonth) {
                $lastMonthGateways++;
            }

            $invoices = rep(PaymentInvoice::class)->findAll(['gateway' => $gateway->adapter]);
            foreach ($invoices as $invoice) {
                if ($invoice->isPaid) {
                    $totalTransactions++;
                    $totalRevenue += $invoice->amount;

                    if ($invoice->paidAt > $today) {
                        $todayTransactions++;
                        $todayRevenue += $invoice->amount;
                    } elseif ($invoice->paidAt > $yesterday && $invoice->paidAt <= $today) {
                        $yesterdayTransactions++;
                        $yesterdayRevenue += $invoice->amount;
                    }
                }
            }
        }

        $gatewaysDiff = $lastMonthGateways > 0
            ? (($totalGateways - $lastMonthGateways) / $lastMonthGateways) * 100
            : ($totalGateways > 0 ? 100 : 0);

        $transactionsDiff = $yesterdayTransactions > 0
            ? (($todayTransactions - $yesterdayTransactions) / $yesterdayTransactions) * 100
            : ($todayTransactions > 0 ? 100 : 0);

        $revenueDiff = $yesterdayRevenue > 0
            ? (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100
            : ($todayRevenue > 0 ? 100 : 0);

        return [
            'total_gateways' => [
                'value' => number_format($totalGateways),
                'diff' => round($gatewaysDiff, 1),
                'icon' => 'bank',
            ],
            'active_gateways' => [
                'value' => number_format($activeGateways) . ' (' . ($totalGateways > 0 ? round(($activeGateways / $totalGateways) * 100) : 0) . '%)',
                'diff' => 0,
                'icon' => 'check-circle',
            ],
            'today_transactions' => [
                'value' => number_format($todayTransactions) . ' / ' . number_format($totalTransactions),
                'diff' => round($transactionsDiff, 1),
                'icon' => 'chart-line-up',
            ],
            'today_revenue' => [
                'value' => number_format($todayRevenue, 2) . ' ' . config('payment.currency'),
                'diff' => round($revenueDiff, 1),
                'icon' => 'money',
            ],
        ];
    }

    /**
     * Выпадающее меню действий для шлюза.
     */
    private function gatewayActionsDropdown(PaymentGateway $gateway): string
    {
        return DropDown::make()
            ->icon('ph.regular.dots-three-outline-vertical')
            ->list([
                DropDownItem::make(__('admin-payment.buttons.edit'))
                    ->redirect(url('/admin/payment/gateways/' . $gateway->id . '/edit'))
                    ->icon('ph.bold.pencil-bold')
                    ->type(Color::OUTLINE_PRIMARY)
                    ->size('small')
                    ->fullWidth(),

                DropDownItem::make($gateway->enabled ? __('admin-payment.buttons.disable') : __('admin-payment.buttons.enable'))
                    ->method('toggleGateway', ['gatewayId' => $gateway->id])
                    ->icon($gateway->enabled ? 'ph.bold.power-bold' : 'ph.bold.play-bold')
                    ->type($gateway->enabled ? Color::OUTLINE_WARNING : Color::OUTLINE_SUCCESS)
                    ->size('small')
                    ->fullWidth(),

                DropDownItem::make(__('admin-payment.buttons.delete'))
                    ->confirm(__('admin-payment.confirms.delete_gateway'))
                    ->method('deleteGateway', ['gatewayId' => $gateway->id])
                    ->icon('ph.bold.trash-bold')
                    ->type(Color::OUTLINE_DANGER)
                    ->size('small')
                    ->fullWidth(),
            ]);
    }
}
