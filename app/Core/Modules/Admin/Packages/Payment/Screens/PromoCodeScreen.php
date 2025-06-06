<?php

namespace Flute\Admin\Packages\Payment\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\PromoCode;
use Flute\Core\Database\Entities\PromoCodeUsage;
use Flute\Admin\Packages\Payment\Services\PaymentService;

class PromoCodeScreen extends Screen
{
    public ?string $name = null;
    public ?string $description = null;
    public ?string $permission = 'admin.payments';

    private PaymentService $paymentService;
    public $promoCodes;
    public $metrics;

    public function mount() : void
    {
        $this->paymentService = app(PaymentService::class);
        $this->promoCodes = rep(PromoCode::class)->select();
        $this->metrics = $this->calculateMetrics();

        $this->name = __('admin-payment.title.promo_codes');
        $this->description = __('admin-payment.title.promo_codes_description');

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-payment.title.promo_codes'));
    }

    /**
     * Calculate metrics for the promo codes dashboard
     */
    private function calculateMetrics() : array
    {
        $now = new \DateTimeImmutable();
        $today = $now->setTime(0, 0);
        $yesterday = $today->modify('-1 day');
        $lastMonth = $today->modify('-30 days');

        $promoCodes = $this->promoCodes;

        $totalCodes = count($promoCodes);
        $activeCodes = 0;
        $totalUsages = 0;
        $totalDiscountAmount = 0;
        $todayUsages = 0;
        $todayDiscountAmount = 0;

        $yesterdayUsages = 0;
        $yesterdayDiscountAmount = 0;
        $lastMonthCodes = 0;

        foreach ($promoCodes as $code) {
            $stats = $this->paymentService->getPromoCodeStats($code);

            if (!$stats['is_expired'] && $stats['remaining_usages'] > 0) {
                $activeCodes++;
            }

            $totalUsages += $stats['total_usages'];
            $totalDiscountAmount += $stats['total_amount'];

            foreach ($code->usages as $usage) {
                if ($usage->invoice->isPaid) {
                    // Calculate the actual discount amount
                    $discountAmount = 0;
                    if ($code->type === 'percentage') {
                        $discountAmount = $usage->invoice->amount * ($code->value / 100);
                    } else {
                        $discountAmount = $code->value;
                    }

                    if ($usage->used_at > $today) {
                        $todayUsages++;
                        $todayDiscountAmount += $discountAmount;
                    } elseif ($usage->used_at > $yesterday && $usage->used_at <= $today) {
                        $yesterdayUsages++;
                        $yesterdayDiscountAmount += $discountAmount;
                    }
                }
            }

            if ($code->createdAt <= $lastMonth) {
                $lastMonthCodes++;
            }
        }

        $codesDiff = $lastMonthCodes > 0
            ? (($totalCodes - $lastMonthCodes) / $lastMonthCodes) * 100
            : ($totalCodes > 0 ? 100 : 0);

        $usagesDiff = $yesterdayUsages > 0
            ? (($todayUsages - $yesterdayUsages) / $yesterdayUsages) * 100
            : ($todayUsages > 0 ? 100 : 0);

        $amountDiff = $yesterdayDiscountAmount > 0
            ? (($todayDiscountAmount - $yesterdayDiscountAmount) / $yesterdayDiscountAmount) * 100
            : ($todayDiscountAmount > 0 ? 100 : 0);

        return [
            'total_codes' => [
                'value' => number_format($totalCodes),
                'diff' => round($codesDiff, 1),
                'icon' => 'ticket'
            ],
            'active_codes' => [
                'value' => number_format($activeCodes) . ' (' . ($totalCodes > 0 ? round(($activeCodes / $totalCodes) * 100) : 0) . '%)',
                'diff' => 0,
                'icon' => 'star'
            ],
            'today_usages' => [
                'value' => number_format($todayUsages) . ' / ' . number_format($totalUsages),
                'diff' => round($usagesDiff, 1),
                'icon' => 'chart-line-up'
            ],
            'today_amount' => [
                'value' => number_format($todayDiscountAmount, 2) . ' ' . config('payment.currency'),
                'diff' => round($amountDiff, 1),
                'icon' => 'money'
            ],
        ];
    }

    /**
     * Командная панель.
     */
    public function commandBar() : array
    {
        return [
            Button::make(__('admin-payment.buttons.add_promo'))
                ->type(Color::PRIMARY)
                ->icon('ph.bold.plus-bold')
                ->modal('addPromoCodeModal'),
        ];
    }

    /**
     * Определение макета экрана.
     */
    public function layout() : array
    {
        return [
            LayoutFactory::metrics([
                __('admin-payment.metrics.total_promo_codes') => 'metrics.total_codes',
                __('admin-payment.metrics.active_promo_codes') => 'metrics.active_codes',
                __('admin-payment.metrics.today_promo_usages') => 'metrics.today_usages',
                __('admin-payment.metrics.discount_amount') => 'metrics.today_amount',
            ])->setIcons([
                __('admin-payment.metrics.total_promo_codes') => 'ticket',
                __('admin-payment.metrics.active_promo_codes') => 'star',
                __('admin-payment.metrics.today_promo_usages') => 'chart-line-up',
                __('admin-payment.metrics.discount_amount') => 'money',
            ]),

            LayoutFactory::table('promoCodes', [
                TD::make('code', __('admin-payment.table.code'))
                    ->render(fn(PromoCode $code) => view('admin-payment::cells.promo-name', ['name' => $code->code]))
                    ->width('200px'),

                TD::make('type', __('admin-payment.table.type'))
                    ->render(fn(PromoCode $code) => view('admin-payment::cells.promo-type', ['type' => $code->type]))
                    ->width('150px'),

                TD::make('value', __('admin-payment.table.value'))
                    ->render(fn(PromoCode $code) => $this->formatValue($code))
                    ->width('150px'),

                TD::make('expires_at', __('admin-payment.table.expires_at'))
                    ->render(fn(PromoCode $code) => $code->expires_at ? $code->expires_at->format('d.m.Y H:i') : '-')
                    ->width('200px'),

                TD::make('status', __('admin-payment.table.status'))
                    ->render(fn(PromoCode $code) => $this->getPromoCodeStatus($code))
                    ->width('150px'),

                TD::make('actions', __('admin-payment.table.actions'))
                    ->render(fn(PromoCode $code) => $this->promoCodeActionsDropdown($code))
                    ->width('100px'),
            ])
                ->searchable([
                    'code',
                    'type',
                    'value'
                ]),
        ];
    }

    /**
     * Форматирование значения промо-кода.
     */
    private function formatValue(PromoCode $code) : string
    {
        return $code->type === 'percentage'
            ? $code->value . '%'
            : number_format($code->value, 2) . ' ' . config('payment.currency');
    }

    /**
     * Получение статуса промо-кода.
     */
    private function getPromoCodeStatus(PromoCode $code)
    {
        $stats = $this->paymentService->getPromoCodeStats($code);
        return view('admin-payment::cells.promo-status', [
            'expired' => $stats['is_expired'],
            'usagesLeft' => $stats['remaining_usages']
        ]);
    }

    /**
     * Выпадающее меню действий для промо-кода.
     */
    private function promoCodeActionsDropdown(PromoCode $code) : string
    {
        return DropDown::make()
            ->icon('ph.regular.dots-three-outline-vertical')
            ->list([
                DropDownItem::make(__('admin-payment.buttons.edit'))
                    ->modal('editPromoCodeModal', ['codeId' => $code->id])
                    ->icon('ph.bold.pencil-bold')
                    ->type(Color::OUTLINE_PRIMARY)
                    ->size('small')
                    ->fullWidth(),

                DropDownItem::make(__('def.details'))
                    ->modal('additionalInfoModal', ['codeId' => $code->id])
                    ->icon('ph.bold.info-bold')
                    ->type(Color::OUTLINE_PRIMARY)
                    ->size('small')
                    ->fullWidth(),

                DropDownItem::make(__('def.history'))
                    ->modal('promoCodeHistoryModal', ['codeId' => $code->id])
                    ->icon('ph.bold.clock-counter-clockwise-bold')
                    ->type(Color::OUTLINE_PRIMARY)
                    ->size('small')
                    ->fullWidth(),

                DropDownItem::make(__('admin-payment.buttons.delete'))
                    ->confirm(__('admin-payment.confirms.delete_promo'))
                    ->method('deletePromoCode', ['codeId' => $code->id])
                    ->icon('ph.bold.trash-bold')
                    ->type(Color::OUTLINE_DANGER)
                    ->size('small')
                    ->fullWidth(),
            ]);
    }

    public function additionalInfoModal(Repository $parameters)
    {
        $code = $this->paymentService->getPromoCodeById($parameters->get('codeId'));
        if (!$code) {
            $this->flashMessage(__('admin-payment.messages.promo_not_found'), 'error');
            return;
        }

        $stats = $this->paymentService->getPromoCodeStats($code);

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('total_usages')
                    ->type('text')
                    ->value($stats['total_usages'])
                    ->readOnly()
            )
                ->label(__('admin-payment.fields.promo.total_usages')),

            LayoutFactory::field(
                Input::make('total_amount')
                    ->type('text')
                    ->value($stats['total_amount'])
                    ->readOnly()
            )
                ->label(__('admin-payment.fields.promo.total_amount')),

            LayoutFactory::field(
                Input::make('remaining_usages')
                    ->type('text')
                    ->value($stats['remaining_usages'])
                    ->readOnly()
            )
                ->label(__('admin-payment.fields.promo.remaining_usages')),

            LayoutFactory::field(
                Input::make('code')
                    ->type('text')
                    ->value($code->code)
                    ->readOnly()
            )
                ->label(__('admin-payment.fields.promo.code.label')),

            LayoutFactory::field(
                Input::make('type')
                    ->type('text')
                    ->value($code->type)
                    ->readOnly()
            )
                ->label(__('admin-payment.fields.promo.type.label')),

            LayoutFactory::field(
                Input::make('value')
                    ->value($code->value)
                    ->readOnly(true)
            )
                ->label(__('admin-payment.fields.promo.value.label')),

            LayoutFactory::field(
                Input::make('max_usages')
                    ->type('text')
                    ->value($code->max_usages ?? 0)
                    ->readOnly()
            )
                ->label(__('admin-payment.fields.promo.max_usages.label')),

            $code->expires_at ? LayoutFactory::field(
                Input::make('expires_at')
                    ->type('datetime-local')
                    ->value($code->expires_at->format('Y-m-d\TH:i'))
                    ->readOnly()
            )
                ->label(__('admin-payment.fields.promo.expires_at.label'))
            : null,
        ])
            ->title(__('admin-payment.title.promo_details', ['name' => $code->code]))
            ->withoutApplyButton()
            ->right();
    }

    /**
     * Модальное окно добавления промо-кода.
     */
    public function addPromoCodeModal(Repository $parameters)
    {
        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('code')
                    ->type('text')
                    ->placeholder(__('admin-payment.fields.promo.code.placeholder'))
            )
                ->label(__('admin-payment.fields.promo.code.label'))
                ->required(),

            LayoutFactory::field(
                Select::make('type')
                    ->options([
                        'amount' => __('admin-payment.fields.promo.type.options.fixed'),
                        'percentage' => __('admin-payment.fields.promo.type.options.percentage'),
                    ])
                    ->placeholder(__('admin-payment.fields.promo.type.placeholder'))
            )
                ->label(__('admin-payment.fields.promo.type.label'))
                ->required(),

            LayoutFactory::field(
                Input::make('value')
                    ->type('number')
                    ->step('0.01')
                    ->placeholder(__('admin-payment.fields.promo.value.placeholder'))
            )
                ->label(__('admin-payment.fields.promo.value.label'))
                ->required(),

            LayoutFactory::field(
                Input::make('max_usages')
                    ->type('number')
                    ->min(1)
                    ->placeholder(__('admin-payment.fields.promo.max_usages.placeholder'))
            )
                ->label(__('admin-payment.fields.promo.max_usages.label'))
                ->small(__('admin-payment.fields.promo.max_usages.help')),

            LayoutFactory::field(
                Input::make('expires_at')
                    ->type('datetime-local')
                    ->placeholder(__('admin-payment.fields.promo.expires_at.placeholder'))
            )
                ->label(__('admin-payment.fields.promo.expires_at.label'))
                ->required()
                ->small(__('admin-payment.fields.promo.expires_at.help')),
        ])
            ->title(__('admin-payment.title.promo_add'))
            ->applyButton(__('admin-payment.buttons.add_promo'))
            ->method('addPromoCode');
    }

    /**
     * Добавление промо-кода.
     */
    public function addPromoCode()
    {
        $data = request()->input();

        $validation = $this->validate([
            'code' => ['required', 'string', 'max-str-len:255', 'unique:promo_codes,code'],
            'type' => ['required', 'string', 'in:amount,percentage'],
            'value' => ['required', 'numeric', 'min:0'],
            'max_usages' => ['nullable', 'min:1'],
            'expires_at' => ['nullable', 'datetime'],
        ], $data);

        if (!$validation) {
            return;
        }

        try {
            $promoCode = new PromoCode();
            $this->paymentService->savePromoCode($promoCode, $data);
            $this->flashMessage(__('admin-payment.messages.promo_added'), 'success');
            $this->closeModal();
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Модальное окно редактирования промо-кода.
     */
    public function editPromoCodeModal(Repository $parameters)
    {
        $codeId = $parameters->get('codeId');
        $promoCode = $this->paymentService->getPromoCodeById($codeId);

        if (!$promoCode) {
            $this->flashMessage(__('admin-payment.messages.promo_not_found'), 'error');
            return;
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('code')
                    ->type('text')
                    ->value($promoCode->code)
                    ->placeholder(__('admin-payment.fields.promo.code.placeholder'))
            )
                ->label(__('admin-payment.fields.promo.code.label'))
                ->required(),

            LayoutFactory::field(
                Select::make('type')
                    ->options([
                        'amount' => __('admin-payment.fields.promo.type.options.fixed'),
                        'percentage' => __('admin-payment.fields.promo.type.options.percentage'),
                    ])
                    ->value($promoCode->type)
                    ->placeholder(__('admin-payment.fields.promo.type.placeholder'))
            )
                ->label(__('admin-payment.fields.promo.type.label'))
                ->required(),

            LayoutFactory::field(
                Input::make('value')
                    ->type('number')
                    ->step('0.01')
                    ->value($promoCode->value)
                    ->placeholder(__('admin-payment.fields.promo.value.placeholder'))
            )
                ->label(__('admin-payment.fields.promo.value.label'))
                ->required(),

            LayoutFactory::field(
                Input::make('max_usages')
                    ->type('number')
                    ->min(1)
                    ->value($promoCode->max_usages ?? 0)
                    ->placeholder(__('admin-payment.fields.promo.max_usages.placeholder'))
            )
                ->label(__('admin-payment.fields.promo.max_usages.label'))
                ->small(__('admin-payment.fields.promo.max_usages.help')),

            LayoutFactory::field(
                Input::make('expires_at')
                    ->type('datetime-local')
                    ->value($promoCode->expires_at->format('Y-m-d\TH:i'))
                    ->placeholder(__('admin-payment.fields.promo.expires_at.placeholder'))
            )
                ->label(__('admin-payment.fields.promo.expires_at.label'))
                ->required()
                ->small(__('admin-payment.fields.promo.expires_at.help')),
        ])
            ->title(__('admin-payment.title.promo_edit', ['name' => $promoCode->code]))
            ->applyButton(__('admin-payment.buttons.save'))
            ->method('updatePromoCode');
    }

    /**
     * Обновление промо-кода.
     */
    public function updatePromoCode()
    {
        $data = request()->input();
        $codeId = $this->modalParams->get('codeId');

        $promoCode = $this->paymentService->getPromoCodeById($codeId);
        if (!$promoCode) {
            $this->flashMessage(__('admin-payment.messages.promo_not_found'), 'error');
            return;
        }

        $validation = $this->validate([
            'code' => ['required', 'string', 'max-str-len:255', 'unique:promo_codes,code,' . $promoCode->id],
            'type' => ['required', 'string', 'in:amount,percentage'],
            'value' => ['required', 'numeric', 'min:0'],
            'max_usages' => ['nullable', 'min:1'],
            'expires_at' => ['nullable', 'datetime'],
        ], $data);

        if (!$validation) {
            return;
        }

        try {
            $this->paymentService->savePromoCode($promoCode, $data);
            $this->flashMessage(__('admin-payment.messages.promo_updated'), 'success');
            $this->closeModal();
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Модальное окно истории использования промо-кода.
     */
    public function promoCodeHistoryModal(Repository &$parameters)
    {
        $codeId = $parameters->get('codeId');
        $promoCode = $this->paymentService->getPromoCodeById($codeId);

        if (!$promoCode) {
            $this->flashMessage(__('admin-payment.messages.promo_not_found'), 'error');
            return;
        }

        $parameters->set('usageHistory', $this->paymentService->getPromoCodeUsageHistory($promoCode));

        return LayoutFactory::modal($parameters, [
            LayoutFactory::table('usageHistory', [
                TD::make('user.name', __('admin-payment.table.user'))
                    ->sort()
                    ->render(fn(PromoCodeUsage $usage) => view('admin-payment::cells.user-name', ['user' => $usage->user]))
                    ->width('200px'),

                TD::make('invoice.amount', __('admin-payment.table.amount'))
                    ->render(fn(PromoCodeUsage $usage) => number_format($usage->invoice->amount, 2) . ' ' . $usage->invoice->currency?->code)
                    ->width('150px'),

                TD::make('used_at', __('admin-payment.table.created_at'))
                    ->sort()
                    ->render(fn(PromoCodeUsage $usage) => $usage->used_at ? $usage->used_at->format('d.m.Y H:i') : '-')
                    ->width('200px'),
            ])->compact(),
        ])
            ->withoutApplyButton()
            ->withoutCloseButton()
            ->title(__('admin-payment.title.promo_history', ['name' => $promoCode->code]))
            ->size('lg');
    }

    /**
     * Удаление промо-кода.
     */
    public function deletePromoCode()
    {
        $codeId = request()->input('codeId');
        $promoCode = $this->paymentService->getPromoCodeById($codeId);

        if (!$promoCode) {
            $this->flashMessage(__('admin-payment.messages.promo_not_found'), 'error');
            return;
        }

        try {
            $this->paymentService->deletePromoCode($promoCode);
            $this->flashMessage(__('admin-payment.messages.promo_deleted'), 'success');
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Получение данных для таблицы.
     */
    public function query() : array
    {
        return [
            'promoCodes' => $this->paymentService->getAllPromoCodes(),
        ];
    }
}

