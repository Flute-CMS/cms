<?php

namespace Flute\Admin\Packages\Payment\Screens;

use Carbon\Carbon;
use DateTimeImmutable;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
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
        $this->invoices = rep(PaymentInvoice::class)->select();
        $this->metrics = $this->calculateMetrics();

        $this->name = __('admin-payment.title.invoices');
        $this->description = __('admin-payment.title.invoices_description');

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-payment.title.invoices'));
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

            LayoutFactory::table('invoices', [
                TD::selection('id'),
                // TD::make('id', __('admin-payment.table.id'))
                //     ->sort()
                //     ->render(fn (PaymentInvoice $invoice) => $invoice->id)
                //     ->width('80px'),

                TD::make('user_id', __('admin-payment.table.user'))
                    ->sort()
                    ->render(static fn (PaymentInvoice $invoice) => view('admin-payment::cells.user-name', ['user' => $invoice->user]))
                    ->width('200px'),

                TD::make('gateway', __('admin-payment.table.payment_system'))
                    ->sort()
                    ->width('200px'),

                TD::make('transactionId', __('admin-payment.table.transaction_id'))
                    ->sort()
                    ->width('200px'),

                TD::make('amount', __('admin-payment.table.amount'))
                    ->render(static fn (PaymentInvoice $invoice) => number_format($invoice->originalAmount, 2) . ' ' . $invoice->currency->code)
                    ->width('150px'),

                TD::make('isPaid', __('admin-payment.table.status'))
                    ->sort()
                    ->render(static fn (PaymentInvoice $invoice) => view('admin-payment::cells.invoice-status', ['invoice' => $invoice]))
                    ->width('150px'),

                TD::make('created_at', __('admin-payment.table.created'))
                    ->sort()
                    ->defaultSort(true, 'desc')
                    ->render(static fn (PaymentInvoice $invoice) => Carbon::parse($invoice->createdAt)->format('d.m.Y H:i:s'))
                    ->width('200px'),

                TD::make('paid_at', __('admin-payment.table.paid_at'))
                    ->sort()
                    ->render(static fn (PaymentInvoice $invoice) => $invoice->paidAt ? Carbon::parse($invoice->paidAt)->format('d.m.Y H:i:s') : '-')
                    ->width('200px'),

                TD::make('actions', __('admin-payment.table.actions'))
                    ->render(fn (PaymentInvoice $invoice) => $this->invoiceActionsDropdown($invoice))
                    ->width('100px'),
            ])
                ->searchable([
                    'id',
                    'gateway',
                    'transactionId',
                ])
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

        $this->invoices = rep(PaymentInvoice::class)->select();
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
        $this->invoices = rep(PaymentInvoice::class)->select();
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
        $this->invoices = rep(PaymentInvoice::class)->select();
        $this->flashMessage(__('admin-payment.messages.invoice_deleted'), 'success');
    }

    private function calculateMetrics(): array
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $yesterday = $today->copy()->subDay();
        $lastMonth = $today->copy()->subDays(30);

        $invoices = $this->invoices;
        $totalInvoices = count($invoices);
        $paidInvoices = 0;
        $totalRevenue = 0;
        $todayInvoices = 0;
        $todayRevenue = 0;

        $yesterdayInvoices = 0;
        $yesterdayRevenue = 0;
        $lastMonthInvoices = 0;

        foreach ($invoices as $invoice) {
            if ($invoice->isPaid) {
                $paidInvoices++;
                $totalRevenue += $invoice->originalAmount;

                if ($invoice->paidAt > $today) {
                    $todayInvoices++;
                    $todayRevenue += $invoice->originalAmount;
                } elseif ($invoice->paidAt > $yesterday && $invoice->paidAt <= $today) {
                    $yesterdayInvoices++;
                    $yesterdayRevenue += $invoice->originalAmount;
                }
            }

            if ($invoice->createdAt <= $lastMonth) {
                $lastMonthInvoices++;
            }
        }

        $invoicesDiff = $lastMonthInvoices > 0
            ? (($totalInvoices - $lastMonthInvoices) / $lastMonthInvoices) * 100
            : ($totalInvoices > 0 ? 100 : 0);

        $paidDiff = $yesterdayInvoices > 0
            ? (($todayInvoices - $yesterdayInvoices) / $yesterdayInvoices) * 100
            : ($todayInvoices > 0 ? 100 : 0);

        $revenueDiff = $yesterdayRevenue > 0
            ? (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100
            : ($todayRevenue > 0 ? 100 : 0);

        return [
            'total_invoices' => [
                'value' => number_format($totalInvoices),
                'diff' => round($invoicesDiff, 1),
                'icon' => 'file-text',
            ],
            'paid_invoices' => [
                'value' => number_format($paidInvoices) . ' (' . ($totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100) : 0) . '%)',
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
