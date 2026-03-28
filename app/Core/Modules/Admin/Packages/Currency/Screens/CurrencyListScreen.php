<?php

namespace Flute\Admin\Packages\Currency\Screens;

use Cycle\Database\Injection\Parameter;
use Flute\Admin\Packages\Currency\Services\CurrencyRateService;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\ButtonGroup;
use Flute\Admin\Platform\Fields\CheckBox;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\Currency;
use Flute\Core\Database\Entities\PaymentGateway;

class CurrencyListScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin.currencies';

    public $currencies;

    public $paymentGateways;

    public function mount(): void
    {
        $this->name = __('admin-currency.title.list');
        $this->description = __('admin-currency.title.description');

        breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(__('admin-currency.title.list'));

        $this->currencies = rep(Currency::class)->select()->load('paymentGateways')->orderBy('id', 'desc');
        $this->paymentGateways = rep(PaymentGateway::class)->select()->orderBy('name', 'asc')->fetchAll();
    }

    public function layout(): array
    {
        return [
            LayoutFactory::table('currencies', [
                TD::selection('id'),
                TD::make('code', __('admin-currency.fields.code.label'))
                    ->render(
                        static fn(Currency $currency) => (
                            $currency->code
                            . '<small class="text-muted d-flex">#'
                            . $currency->id
                            . '</small>'
                        ),
                    )
                    ->cantHide()
                    ->width('100px'),

                TD::make('minimum_value', __('admin-currency.fields.minimum_value.label'))
                    ->popover(__('admin-currency.fields.minimum_value.help'))
                    ->width('100px'),

                TD::make('exchange_rate', __('admin-currency.fields.rate.label'))
                    ->popover(__('admin-currency.fields.rate.help'))
                    ->render(
                        static fn(Currency $currency) => (
                            $currency->exchange_rate
                            . (
                                $currency->auto_rate
                                    ? ' <span class="badge badge-outline-primary" style="font-size:10px">'
                                    . __('admin-currency.fields.auto_rate.badge')
                                    . ( $currency->rate_markup > 0 ? ' +' . $currency->rate_markup . '%' : '' )
                                    . '</span>'
                                    : ''
                            )
                        ),
                    )
                    ->width('150px'),

                TD::make('actions', __('admin-currency.buttons.actions'))
                    ->width('200px')
                    ->alignCenter()
                    ->render(static fn(Currency $currency) => DropDown::make()
                        ->icon('ph.regular.dots-three-outline-vertical')
                        ->list([
                            DropDownItem::make(__('admin-currency.buttons.edit'))
                                ->modal('editCurrencyModal', ['currency' => $currency->id])
                                ->icon('ph.bold.pencil-bold')
                                ->type(Color::OUTLINE_PRIMARY)
                                ->size('small')
                                ->fullWidth(),
                            DropDownItem::make(__('admin-currency.buttons.delete'))
                                ->confirm(__('admin-currency.confirms.delete_currency'))
                                ->method('deleteCurrency', ['id' => $currency->id])
                                ->icon('ph.bold.trash-bold')
                                ->type(Color::OUTLINE_DANGER)
                                ->size('small')
                                ->fullWidth(),
                        ])),
            ])
                ->empty('ph.regular.coins', __('admin-currency.empty.title'), __('admin-currency.empty.sub'))
                ->emptyButton(
                    Button::make(__('admin-currency.buttons.add'))
                        ->icon('ph.bold.plus-bold')
                        ->modal('createCurrencyModal'),
                )
                ->searchable(['code'])
                ->bulkActions([
                    Button::make(__('admin.bulk.delete_selected'))
                        ->icon('ph.bold.trash-bold')
                        ->type(Color::OUTLINE_DANGER)
                        ->confirm(__('admin.confirms.delete_selected'))
                        ->method('bulkDeleteCurrencies'),
                ])
                ->commands([
                    Button::make(__('admin-currency.buttons.update_rates'))
                        ->icon('ph.bold.arrows-clockwise-bold')
                        ->size('medium')
                        ->type(Color::OUTLINE_PRIMARY)
                        ->confirm(__('admin-currency.confirms.update_rates'))
                        ->method('updateAllRates'),
                    Button::make(__('admin-currency.buttons.add'))
                        ->icon('ph.bold.plus-bold')
                        ->size('medium')
                        ->modal('createCurrencyModal'),
                ]),
        ];
    }

    /**
     * Модальное окно для добавления новой валюты
     */
    public function createCurrencyModal(Repository $parameters)
    {
        $rateMode = request()->input('rate_mode', 'manual');

        $paymentGatewayCheckboxes = [];
        foreach ($this->paymentGateways as $pg) {
            $paymentGatewayCheckboxes[] = LayoutFactory::field(
                CheckBox::make("payment_gateways.{$pg->id}")
                    ->popover($pg->adapter)
                    ->label($pg->name),
            );
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('code')->type('text')->placeholder(__('admin-currency.fields.code.placeholder')),
            )
                ->label(__('admin-currency.fields.code.label'))
                ->required()
                ->popover(__('admin-currency.fields.code.popover'))
                ->small(__('admin-currency.fields.code.help')),

            LayoutFactory::field(
                Input::make('minimum_value')
                    ->type('number')
                    ->placeholder(__('admin-currency.fields.minimum_value.placeholder')),
            )
                ->label(__('admin-currency.fields.minimum_value.label'))
                ->required()
                ->popover(__('admin-currency.fields.minimum_value.popover'))
                ->small(__('admin-currency.fields.minimum_value.help')),

            LayoutFactory::field(
                ButtonGroup::make('rate_mode')
                    ->options([
                        'manual' => [
                            'label' => __('admin-currency.fields.rate_mode.manual'),
                            'icon' => 'ph.bold.pencil-simple-bold',
                        ],
                        'auto' => [
                            'label' => __('admin-currency.fields.rate_mode.auto'),
                            'icon' => 'ph.bold.arrows-clockwise-bold',
                        ],
                    ])
                    ->value($rateMode)
                    ->yoyo()
                    ->fullWidth(true)
                    ->size('small'),
            )
                ->label(__('admin-currency.fields.rate_mode.label'))
                ->popover(__('admin-currency.fields.rate_mode.popover')),

            LayoutFactory::field(
                Input::make('exchange_rate')
                    ->type('number')
                    ->step('0.0001')
                    ->placeholder(__('admin-currency.fields.rate.placeholder')),
            )
                ->label(__('admin-currency.fields.rate.label'))
                ->required()
                ->popover(__('admin-currency.fields.rate.popover'))
                ->small(__('admin-currency.fields.rate.help'))
                ->setVisible($rateMode === 'manual'),

            LayoutFactory::field(
                Input::make('rate_markup')
                    ->type('number')
                    ->step('0.01')
                    ->value(0)
                    ->placeholder(__('admin-currency.fields.rate_markup.placeholder')),
            )
                ->label(__('admin-currency.fields.rate_markup.label'))
                ->popover(__('admin-currency.fields.rate_markup.popover'))
                ->small(__('admin-currency.fields.rate_markup.help'))
                ->setVisible($rateMode === 'auto'),

            LayoutFactory::field(
                Input::make('preset_amounts')
                    ->type('text')
                    ->placeholder(__('admin-currency.fields.preset_amounts.placeholder')),
            )
                ->label(__('admin-currency.fields.preset_amounts.label'))
                ->popover(__('admin-currency.fields.preset_amounts.popover'))
                ->small(__('admin-currency.fields.preset_amounts.help')),

            ...$paymentGatewayCheckboxes,
        ])
            ->title(__('admin-currency.title.create'))
            ->applyButton(__('admin-currency.buttons.add'))
            ->method('saveCurrency');
    }

    /**
     * Сохранение новой валюты
     */
    public function saveCurrency()
    {
        $data = request()->input();

        $selectedPaymentGateways = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'payment_gateways_') === 0 && $value === 'on') {
                $pgId = str_replace('payment_gateways_', '', $key);
                if (is_numeric($pgId)) {
                    $selectedPaymentGateways[] = (int) $pgId;
                }
            }
        }

        $rules = [
            'code' => ['required', 'string', 'unique:currencies,code', 'size:3'],
            'minimum_value' => ['required', 'numeric', 'min:0'],
        ];

        if (( $data['rate_mode'] ?? 'manual' ) === 'manual') {
            $rules['exchange_rate'] = ['required', 'numeric', 'min:0'];
        }

        $validation = $this->validate($rules, $data);

        if (!$validation) {
            return;
        }

        if (!empty($selectedPaymentGateways)) {
            $validPaymentGateways = PaymentGateway::query()
                ->where('id', 'in', new Parameter($selectedPaymentGateways))
                ->count();

            if ($validPaymentGateways !== count($selectedPaymentGateways)) {
                $this->flashMessage(__('admin-currency.messages.invalid_payment_gateways'), 'error');

                return;
            }
        }

        $isAutoRate = ( $data['rate_mode'] ?? 'manual' ) === 'auto';

        $currency = new Currency();
        $currency->code = strtoupper($data['code']);
        $currency->minimum_value = $data['minimum_value'];
        $currency->exchange_rate = $isAutoRate ? 1 : (float) ( $data['exchange_rate'] ?? 1 );
        $currency->auto_rate = $isAutoRate;
        $currency->rate_markup = $isAutoRate ? (float) ( $data['rate_markup'] ?? 0 ) : 0;

        if (!empty($data['preset_amounts'])) {
            $presets = array_map('trim', explode(',', $data['preset_amounts']));
            $currency->setPresetAmounts($presets);
        }

        $currency->save();

        if (!empty($selectedPaymentGateways)) {
            $paymentGateways = PaymentGateway::query()
                ->where('id', 'in', new Parameter($selectedPaymentGateways))
                ->fetchAll();
            foreach ($paymentGateways as $paymentGateway) {
                $currency->addPayment($paymentGateway);
            }
        }
        $currency->save();

        cache()->delete('flute.currencies');

        $this->flashMessage(__('admin-currency.messages.save_success'), 'success');
        $this->closeModal();
        $this->currencies = rep(Currency::class)->select()->orderBy('id', 'desc');
    }

    /**
     * Модальное окно для редактирования валюты
     */
    public function editCurrencyModal(Repository $parameters)
    {
        $currencyId = $parameters->get('currency');
        $currency = Currency::findByPK($currencyId);
        if (!$currency) {
            $this->flashMessage(__('admin-currency.messages.currency_not_found'), 'error');

            return;
        }

        $rateMode = request()->input('rate_mode', $currency->auto_rate ? 'auto' : 'manual');

        $paymentGatewayCheckboxes = [];
        foreach ($this->paymentGateways as $pg) {
            $isChecked = $currency->hasPayment($pg);
            $paymentGatewayCheckboxes[] = LayoutFactory::field(
                CheckBox::make("payment_gateways.{$pg->id}")
                    ->label($pg->name)
                    ->popover($pg->adapter)
                    ->value($isChecked),
            );
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('code')
                    ->type('text')
                    ->placeholder(__('admin-currency.fields.code.placeholder'))
                    ->value($currency->code),
            )
                ->label(__('admin-currency.fields.code.label'))
                ->required()
                ->popover(__('admin-currency.fields.code.popover'))
                ->small(__('admin-currency.fields.code.help')),

            LayoutFactory::field(
                Input::make('minimum_value')
                    ->type('number')
                    ->placeholder(__('admin-currency.fields.minimum_value.placeholder'))
                    ->value($currency->minimum_value),
            )
                ->label(__('admin-currency.fields.minimum_value.label'))
                ->required()
                ->popover(__('admin-currency.fields.minimum_value.popover'))
                ->small(__('admin-currency.fields.minimum_value.help')),

            LayoutFactory::field(
                ButtonGroup::make('rate_mode')
                    ->options([
                        'manual' => [
                            'label' => __('admin-currency.fields.rate_mode.manual'),
                            'icon' => 'ph.bold.pencil-simple-bold',
                        ],
                        'auto' => [
                            'label' => __('admin-currency.fields.rate_mode.auto'),
                            'icon' => 'ph.bold.arrows-clockwise-bold',
                        ],
                    ])
                    ->value($rateMode)
                    ->yoyo()
                    ->fullWidth(true)
                    ->size('small'),
            )
                ->label(__('admin-currency.fields.rate_mode.label'))
                ->popover(__('admin-currency.fields.rate_mode.popover')),

            LayoutFactory::field(
                Input::make('exchange_rate')
                    ->type('number')
                    ->step('0.0001')
                    ->placeholder(__('admin-currency.fields.rate.placeholder'))
                    ->value($currency->exchange_rate),
            )
                ->label(__('admin-currency.fields.rate.label'))
                ->required()
                ->popover(__('admin-currency.fields.rate.popover'))
                ->small(__('admin-currency.fields.rate.help'))
                ->setVisible($rateMode === 'manual'),

            LayoutFactory::field(
                Input::make('rate_markup')
                    ->type('number')
                    ->step('0.01')
                    ->value($currency->rate_markup)
                    ->placeholder(__('admin-currency.fields.rate_markup.placeholder')),
            )
                ->label(__('admin-currency.fields.rate_markup.label'))
                ->popover(__('admin-currency.fields.rate_markup.popover'))
                ->small(__('admin-currency.fields.rate_markup.help'))
                ->setVisible($rateMode === 'auto'),

            LayoutFactory::field(
                Input::make('preset_amounts')
                    ->type('text')
                    ->placeholder(__('admin-currency.fields.preset_amounts.placeholder'))
                    ->value(implode(', ', $currency->getPresetAmounts())),
            )
                ->label(__('admin-currency.fields.preset_amounts.label'))
                ->popover(__('admin-currency.fields.preset_amounts.popover'))
                ->small(__('admin-currency.fields.preset_amounts.help')),

            ...$paymentGatewayCheckboxes,
        ])
            ->title(__('admin-currency.title.edit'))
            ->applyButton(__('admin-currency.buttons.save'))
            ->method('updateCurrency');
    }

    /**
     * Обновление существующей валюты
     */
    public function updateCurrency()
    {
        $data = request()->input();
        $currencyId = $this->modalParams->get('currency');

        $currency = Currency::findByPK($currencyId);
        if (!$currency) {
            $this->flashMessage(__('admin-currency.messages.currency_not_found'), 'error');

            return;
        }

        $selectedPaymentGateways = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'payment_gateways_') === 0 && $value === 'on') {
                $pgId = str_replace('payment_gateways_', '', $key);
                if (is_numeric($pgId)) {
                    $selectedPaymentGateways[] = (int) $pgId;
                }
            }
        }

        $isAutoRate = ( $data['rate_mode'] ?? 'manual' ) === 'auto';

        $rules = [
            'code' => ['required', 'string', "unique:currencies,code,{$currency->id}", 'size:3'],
            'minimum_value' => ['required', 'numeric', 'min:0'],
        ];

        if (!$isAutoRate) {
            $rules['exchange_rate'] = ['required', 'numeric', 'min:0'];
        }

        $validation = $this->validate($rules, $data);

        if (!$validation) {
            return;
        }

        if (!empty($selectedPaymentGateways)) {
            $validPaymentGateways = PaymentGateway::query()
                ->where('id', 'in', new Parameter($selectedPaymentGateways))
                ->count();

            if ($validPaymentGateways !== count($selectedPaymentGateways)) {
                $this->flashMessage(__('admin-currency.messages.invalid_payment_gateways'), 'error');

                return;
            }
        }

        $currency->code = strtoupper($data['code']);
        $currency->minimum_value = $data['minimum_value'];
        $currency->auto_rate = $isAutoRate;

        if ($isAutoRate) {
            $currency->rate_markup = (float) ( $data['rate_markup'] ?? 0 );
        } else {
            $currency->exchange_rate = (float) ( $data['exchange_rate'] ?? 1 );
            $currency->rate_markup = 0;
        }

        if (!empty($data['preset_amounts'])) {
            $presets = array_map('trim', explode(',', $data['preset_amounts']));
            $currency->setPresetAmounts($presets);
        } else {
            $currency->preset_amounts = null;
        }

        $currency->save();

        $currency->clearPayments();

        if (!empty($selectedPaymentGateways)) {
            $paymentGateways = PaymentGateway::query()
                ->where('id', 'in', new Parameter($selectedPaymentGateways))
                ->fetchAll();
            foreach ($paymentGateways as $paymentGateway) {
                $currency->addPayment($paymentGateway);
            }
        }

        $currency->save();

        cache()->delete('flute.currencies');

        $this->flashMessage(__('admin-currency.messages.save_success'), 'success');
        $this->currencies = rep(Currency::class)->select()->orderBy('id', 'desc');
        $this->closeModal();
    }

    /**
     * Удаление валюты
     */
    public function deleteCurrency()
    {
        $id = request()->input('id');

        $currency = Currency::findByPK($id);
        if (!$currency) {
            $this->flashMessage(__('admin-currency.messages.currency_not_found'), 'error');

            return;
        }

        // Очищаем связи с платежными системами перед удалением
        $currency->clearPayments();
        $currency->save();

        $currency->delete();

        cache()->delete('flute.currencies');

        $this->flashMessage(__('admin-currency.messages.delete_success'), 'success');
        $this->currencies = rep(Currency::class)->select()->orderBy('id', 'desc');
    }

    public function bulkDeleteCurrencies(): void
    {
        $ids = request()->input('selected', []);
        if (!$ids) {
            return;
        }
        foreach ($ids as $id) {
            $currency = Currency::findByPK($id);
            if (!$currency) {
                continue;
            }
            $currency->clearPayments();
            $currency->save();
            $currency->delete();
        }

        cache()->delete('flute.currencies');

        $this->currencies = rep(Currency::class)->select()->orderBy('id', 'desc');
        $this->flashMessage(__('admin-currency.messages.delete_success'), 'success');
    }

    public function updateAllRates(): void
    {
        $service = new CurrencyRateService();
        $updated = $service->updateAutoRates();

        $this->currencies = rep(Currency::class)->select()->orderBy('id', 'desc');

        if ($updated === 0) {
            $this->flashMessage(__('admin-currency.messages.no_auto_currencies'), 'warning');

            return;
        }

        $this->flashMessage(__('admin-currency.messages.rates_updated', ['count' => $updated]), 'success');
    }
}
