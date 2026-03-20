<?php

namespace Flute\Admin\Packages\Payment\Screens;

use Carbon\Carbon;
use Cycle\Database\Injection\Fragment;
use DateTimeImmutable;
use DateTimeZone;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\Filters;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Database\Entities\PaymentInvoice;
use Throwable;

class PaymentInvoiceScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin.payments';

    public $invoices;

    public $metrics;

    public function mount(): void
    {
        $query = PaymentInvoice::query()->load('promoCode')->load('currency');

        // Применяем фильтр статуса
        $status = request()->input('status', 'all');
        if ($status === 'paid') {
            $query->where('isPaid', true);
        } elseif ($status === 'unpaid') {
            $query->where('isPaid', false);
        }

        // Применяем фильтр шлюза
        $gateway = request()->input('gateway');
        if ($gateway) {
            $query->where('gateway', $gateway);
        }

        // Применяем фильтр дат
        $dateFrom = request()->input('date_from');
        $dateTo = request()->input('date_to');

        if ($dateFrom) {
            $query->where('createdAt', '>=', new DateTimeImmutable($dateFrom . ' 00:00:00'));
        }

        if ($dateTo) {
            $query->where('createdAt', '<=', new DateTimeImmutable($dateTo . ' 23:59:59'));
        }

        // Применяем фильтр периода
        $period = request()->input('period', 'all');
        if ($period !== 'all') {
            $days = match ($period) {
                '7d' => 7,
                '30d' => 30,
                '90d' => 90,
                '180d' => 180,
                '365d' => 365,
                default => null,
            };
            if ($days !== null) {
                $dateFrom = ( new DateTimeImmutable() )->modify("-{$days} days");
                $query->where('createdAt', '>=', $dateFrom);
            }
        }

        $this->invoices = $query;
        $this->metrics = $this->calculateMetrics();

        $this->name = __('admin-payment.title.invoices');
        $this->description = __('admin-payment.title.invoices_description');

        breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(__('admin-payment.title.invoices'));
    }

    public function layout(): array
    {
        return [
            LayoutFactory::metrics([
                __('admin-payment.metrics.total_invoices') => 'metrics.total_invoices',
                __('admin-payment.metrics.paid_invoices') => 'metrics.paid_invoices',
                __('admin-payment.metrics.today_invoices') => 'metrics.today_invoices',
                __('admin-payment.metrics.today_revenue') => 'metrics.today_revenue',
            ])->setIcons([
                __('admin-payment.metrics.total_invoices') => 'file-text',
                __('admin-payment.metrics.paid_invoices') => 'check-circle',
                __('admin-payment.metrics.today_invoices') => 'chart-line-up',
                __('admin-payment.metrics.today_revenue') => 'money',
            ]),

            $this->getFilters(),

            LayoutFactory::table('invoices', [
                TD::selection('id'),

                TD::make('user_id', __('def.user'))
                    ->sort()
                    ->render(static fn(PaymentInvoice $invoice) => view('admin-payment::cells.user-name', [
                        'user' => $invoice->user,
                    ]))
                    ->width('200px'),

                TD::make('gateway', __('admin-payment.table.payment_system'))
                    ->sort()
                    ->render(static function (PaymentInvoice $invoice) {
                        $html = e($invoice->gateway);
                        if ($invoice->transactionId) {
                            $tid = e($invoice->transactionId);
                            $short = mb_strlen($tid) > 16 ? mb_substr($tid, 0, 16) . '…' : $tid;
                            $html .=
                                '<br><code data-tooltip="'
                                . $tid
                                . '" style="font-size:11px;color:var(--text-500);cursor:help">'
                                . $short
                                . '</code>';
                        }

                        return $html;
                    }),

                TD::make('amount', __('def.amount'))
                    ->sort()
                    ->render(static function (PaymentInvoice $invoice) {
                        $paymentCur = e($invoice->currency->code ?? '');
                        $systemCur = e(config('lk.currency_view', ''));
                        $orig = number_format($invoice->originalAmount, 2);
                        $base = $invoice->amount;
                        $promo = $invoice->promoCode;

                        $promoBonus = 0;
                        if ($promo && $invoice->isPaid) {
                            $promoBonus = match ($promo->type) {
                                'percentage' => round($invoice->originalAmount * ( $promo->value / 100 ), 2),
                                'amount' => $promo->value,
                                default => 0,
                            };
                        }

                        $gateway = PaymentGateway::findOne(['adapter' => $invoice->gateway]);
                        $gatewayBonus = 0;
                        if ($gateway && $gateway->bonus > 0 && $invoice->isPaid) {
                            $gatewayBonus = round($base * ( $gateway->bonus / 100 ), 2);
                        }

                        $total = $base + $promoBonus + $gatewayBonus;

                        $html = '<div style="line-height:1.5">';
                        $html .=
                            '<span style="font-weight:600;font-size:14px">' . $orig . ' ' . $paymentCur . '</span>';

                        // Base conversion
                        $html .=
                            '<br><span style="font-size:12px;color:var(--text-500)">'
                            . '→ '
                            . number_format($base, 2)
                            . ' '
                            . $systemCur
                            . '</span>';

                        // Promo bonus
                        if ($promoBonus > 0) {
                            $promoTip = __('admin-payment.table.promo') . ': ' . $promo->code;
                            if ($promo->type === 'percentage') {
                                $promoTip .= ' (' . $promo->value . '%)';
                            } else {
                                $promoTip .= ' (+' . number_format($promo->value, 2) . ')';
                            }

                            $html .=
                                '<br><span data-tooltip="'
                                . e($promoTip)
                                . '" style="font-size:12px;color:var(--success);cursor:help">'
                                . '+ '
                                . number_format($promoBonus, 2)
                                . ' '
                                . $systemCur
                                . ' <span class="badge accent" style="font-size:9px;vertical-align:middle">'
                                . e($promo->code)
                                . '</span></span>';
                        }

                        // Gateway bonus
                        if ($gatewayBonus > 0) {
                            $html .=
                                '<br><span data-tooltip="'
                                . e(__('admin-payment.table.gateway_bonus') . ' ' . $gateway->bonus . '%')
                                . '"'
                                . ' style="font-size:12px;color:var(--success);cursor:help">'
                                . '+ '
                                . number_format($gatewayBonus, 2)
                                . ' '
                                . $systemCur
                                . '</span>';
                        }

                        // Total if bonuses exist
                        if ($promoBonus > 0 || $gatewayBonus > 0) {
                            $html .=
                                '<br><span style="font-size:12px;font-weight:600">'
                                . '= '
                                . number_format($total, 2)
                                . ' '
                                . $systemCur
                                . '</span>';
                        }

                        $html .= '</div>';

                        return $html;
                    }),

                TD::make('isPaid', __('def.status'))
                    ->sort()
                    ->render(static fn(PaymentInvoice $invoice) => view('admin-payment::cells.invoice-status', [
                        'invoice' => $invoice,
                    ])),

                TD::make('created_at', __('def.created_at'))
                    ->sort()
                    ->defaultSort(true, 'desc')
                    ->render(static function (PaymentInvoice $invoice) {
                        $tz = config('app.timezone', 'UTC');
                        $created = Carbon::parse($invoice->createdAt)->setTimezone($tz);

                        $html =
                            '<span data-tooltip="'
                            . e($created->format('d.m.Y H:i:s'))
                            . '">'
                            . $created->format('d.m.Y H:i')
                            . '</span>';

                        if ($invoice->paidAt) {
                            $paid = Carbon::parse($invoice->paidAt)->setTimezone($tz);
                            $html .=
                                '<br><span style="font-size:11px;color:var(--success)"'
                                . ' data-tooltip="'
                                . e(__('admin-payment.table.paid_at') . ': ' . $paid->format('d.m.Y H:i:s'))
                                . '">'
                                . '✓ '
                                . $paid->format('d.m.Y H:i')
                                . '</span>';
                        }

                        return $html;
                    }),

                TD::make('actions', '')
                    ->render(fn(PaymentInvoice $invoice) => $this->invoiceActionsDropdown($invoice))
                    ->width(80),
            ])
                ->empty(
                    'ph.regular.receipt',
                    __('admin-payment.empty.invoices.title'),
                    __('admin-payment.empty.invoices.sub'),
                )
                ->searchable([
                    'id',
                    'gateway',
                    'transactionId',
                ])
                ->exportable(true, 'payment_invoices')
                ->bulkActions([
                    Button::make(__('admin.bulk.enable_selected'))
                        ->icon('ph.bold.check-circle-bold')
                        ->type(Color::OUTLINE_SUCCESS)
                        ->method('bulkMarkInvoicesPaid'),

                    Button::make(__('admin.bulk.delete_selected'))
                        ->icon('ph.bold.trash-bold')
                        ->type(Color::OUTLINE_DANGER)
                        ->confirm(__('admin.confirms.delete_selected'))
                        ->method('bulkDeleteInvoices'),
                ]),
        ];
    }

    public function deleteInvoice(): void
    {
        $invoiceId = request()->input('invoiceId');

        if (!$invoiceId) {
            $this->flashMessage(__('admin-payment.messages.invoice_id_required'), 'error');

            return;
        }

        $invoice = PaymentInvoice::findByPK($invoiceId);

        if ($invoice) {
            $invoice->delete();
            $this->flashMessage(__('admin-payment.messages.invoice_deleted'), 'success');
        } else {
            $this->flashMessage(__('admin-payment.messages.invoice_not_found'), 'error');
        }
    }

    public function markAsPaid()
    {
        $transactionId = request()->input('transactionId');

        if (!$transactionId) {
            $this->flashMessage(__('admin-payment.messages.transaction_id_required'), 'error');

            return;
        }

        payments()->processor()->setInvoiceAsPaid($transactionId);

        $this->flashMessage(__('admin-payment.messages.invoice_marked_paid'), 'success');

        $this->invoices = PaymentInvoice::query();
    }

    public function bulkMarkInvoicesPaid(): void
    {
        $ids = request()->input('selected', []);
        if (!$ids) {
            return;
        }
        foreach ($ids as $id) {
            $invoice = PaymentInvoice::findByPK((int) $id);
            if (!$invoice) {
                continue;
            }
            if ($invoice->isPaid) {
                continue;
            }

            try {
                if (!empty($invoice->transactionId)) {
                    payments()->processor()->setInvoiceAsPaid($invoice->transactionId);
                } else {
                    // Fallback: mark directly if processor needs transactionId
                    $invoice->isPaid = true;
                    $invoice->paidAt = new DateTimeImmutable();
                    $invoice->save();
                }
            } catch (Throwable $e) {
                // continue
            }
        }
        $this->invoices = PaymentInvoice::query();
        $this->flashMessage(__('admin-payment.messages.invoice_marked_paid'), 'success');
    }

    public function bulkDeleteInvoices(): void
    {
        $ids = request()->input('selected', []);
        if (!$ids) {
            return;
        }
        foreach ($ids as $id) {
            $invoice = PaymentInvoice::findByPK((int) $id);
            if (!$invoice) {
                continue;
            }

            try {
                $invoice->delete();
            } catch (Throwable $e) {
                // continue
            }
        }
        $this->invoices = PaymentInvoice::query();
        $this->flashMessage(__('admin-payment.messages.invoice_deleted'), 'success');
    }

    /**
     * Вычисляет метрики через SQL агрегатные функции.
     * Оптимизировано для больших объёмов данных.
     */
    private function calculateMetrics(): array
    {
        $appTz = new DateTimeZone(config('app.timezone', 'UTC'));
        $dbTz = new DateTimeZone('UTC');

        $now = new DateTimeImmutable('now', $appTz);
        $today = $now->setTime(0, 0);
        $yesterday = $today->modify('-1 day');
        $lastMonth = $today->modify('-30 days');

        $todayDb = $today->setTimezone($dbTz);
        $yesterdayDb = $yesterday->setTimezone($dbTz);
        $lastMonthDb = $lastMonth->setTimezone($dbTz);

        // Общее количество счетов
        $totalInvoices = PaymentInvoice::query()->count();

        // Оплаченные счета
        $paidInvoices = PaymentInvoice::query()->where('isPaid', true)->count();

        $todayInvoices = PaymentInvoice::query()
            ->where('isPaid', true)
            ->where('paidAt', '>', $todayDb)
            ->count();

        $todayRevenueQuery = PaymentInvoice::query()
            ->where('isPaid', true)
            ->where('paidAt', '>', $todayDb)
            ->buildQuery();
        $todayRevenueQuery->columns([new Fragment('COALESCE(SUM(original_amount), 0) as sum')]);
        $todayRevenue = (float) ( $todayRevenueQuery->limit(1)->fetchAll()[0]['sum'] ?? 0 );

        $yesterdayInvoices = PaymentInvoice::query()
            ->where('isPaid', true)
            ->where('paidAt', '>', $yesterdayDb)
            ->where('paidAt', '<=', $todayDb)
            ->count();

        $yesterdayRevenueQuery = PaymentInvoice::query()
            ->where('isPaid', true)
            ->where('paidAt', '>', $yesterdayDb)
            ->where('paidAt', '<=', $todayDb)
            ->buildQuery();
        $yesterdayRevenueQuery->columns([new Fragment('COALESCE(SUM(original_amount), 0) as sum')]);
        $yesterdayRevenue = (float) ( $yesterdayRevenueQuery->limit(1)->fetchAll()[0]['sum'] ?? 0 );

        $lastMonthInvoices = PaymentInvoice::query()->where('createdAt', '<=', $lastMonthDb)->count();

        // Вычисляем разницу в процентах
        $invoicesDiff = $lastMonthInvoices > 0
            ? ( ( $totalInvoices - $lastMonthInvoices ) / $lastMonthInvoices ) * 100
            : ( $totalInvoices > 0 ? 100 : 0 );

        $paidDiff = $yesterdayInvoices > 0
            ? ( ( $todayInvoices - $yesterdayInvoices ) / $yesterdayInvoices ) * 100
            : ( $todayInvoices > 0 ? 100 : 0 );

        $revenueDiff = $yesterdayRevenue > 0
            ? ( ( $todayRevenue - $yesterdayRevenue ) / $yesterdayRevenue ) * 100
            : ( $todayRevenue > 0 ? 100 : 0 );

        return [
            'total_invoices' => [
                'value' => number_format($totalInvoices),
                'diff' => round($invoicesDiff, 1),
                'icon' => 'file-text',
            ],
            'paid_invoices' => [
                'value' =>
                    number_format($paidInvoices)
                        . ' ('
                        . ( $totalInvoices > 0 ? round(( $paidInvoices / $totalInvoices ) * 100) : 0 )
                        . '%)',
                'diff' => 0,
                'icon' => 'check-circle',
            ],
            'today_invoices' => [
                'value' => number_format($todayInvoices),
                'diff' => round($paidDiff, 1),
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
     * Получить компонент фильтров.
     */
    private function getFilters(): Filters
    {
        // Получаем уникальные шлюзы через raw query
        $query = PaymentInvoice::query()->buildQuery();
        $query->columns([new Fragment('DISTINCT gateway')]);
        $gateways = $query->fetchAll();

        $gatewayOptions = ['' => __('admin.filters.status.all')];
        foreach ($gateways as $row) {
            if (!empty($row['gateway'])) {
                $gatewayOptions[$row['gateway']] = $row['gateway'];
            }
        }

        return Filters::make()
            ->buttonGroup(
                'status',
                __('admin.filters.status_label'),
                [
                    'all' => __('admin.filters.status.all'),
                    'paid' => __('admin-payment.status.paid'),
                    'unpaid' => __('admin-payment.status.unpaid'),
                ],
                'all',
            )
            ->select('gateway', __('admin-payment.table.payment_system'), $gatewayOptions)
            ->dateRange('date', __('def.created_at'))
            ->period('period', __('admin.filters.period'), 'all')
            ->compact();
    }

    private function invoiceActionsDropdown(PaymentInvoice $invoice): string
    {
        return DropDown::make()
            ->icon('ph.regular.dots-three-outline-vertical')
            ->list([
                DropDownItem::make(__('admin-payment.buttons.mark_as_paid'))
                    ->method('markAsPaid', ['transactionId' => $invoice->transactionId])
                    ->icon('ph.bold.check-circle-bold')
                    ->confirm(__('admin-payment.confirms.mark_as_paid'), 'info')
                    ->type(Color::OUTLINE_SUCCESS)
                    ->setVisible(!$invoice->isPaid)
                    ->size('small')
                    ->fullWidth(),

                DropDownItem::make(__('admin-payment.buttons.delete'))
                    ->confirm(__('admin-payment.confirms.delete_invoice'))
                    ->method('deleteInvoice', ['invoiceId' => $invoice->id])
                    ->icon('ph.bold.trash-bold')
                    ->type(Color::OUTLINE_DANGER)
                    ->size('small')
                    ->fullWidth(),
            ]);
    }
}
