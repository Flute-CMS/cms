<?php

namespace Flute\Admin\Packages\Currency\Screens;

use Cycle\Database\Injection\Parameter;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
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

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-currency.title.list'));

        $this->currencies = rep(Currency::class)->select()->load('paymentGateways')->orderBy('id', 'desc');
        $this->paymentGateways = rep(PaymentGateway::class)->select()->orderBy('name', 'asc')->fetchAll();
    }

    public function layout(): array
    {
        return [
            LayoutFactory::table('currencies', [
                TD::make('code', __('admin-currency.fields.code.label'))
                    ->render(fn (Currency $currency) => $currency->code . '<small class="text-muted d-flex">#' . $currency->id . '</small>')
                    ->cantHide()
                    ->width('100px'),

                TD::make('minimum_value', __('admin-currency.fields.minimum_value.label'))
                    ->popover(__('admin-currency.fields.minimum_value.help'))
                    ->width('100px'),

                TD::make('exchange_rate', __('admin-currency.fields.rate.label'))
                    ->popover(__('admin-currency.fields.rate.help'))
                    ->width('100px'),

                TD::make('actions', __('admin-currency.buttons.actions'))
                    ->width('200px')
                    ->alignCenter()
                    ->render(
                        fn (Currency $currency) => DropDown::make()
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
                            ])
                    ),
            ])
                ->searchable(['code'])
                ->commands([
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
        $paymentGateways = $this->paymentGateways;

        $paymentGatewayCheckboxes = [];
        foreach ($paymentGateways as $pg) {
            $paymentGatewayCheckboxes[] = LayoutFactory::field(
                CheckBox::make("payment_gateways.{$pg->id}")
                    ->popover($pg->adapter)
                    ->label($pg->name)
            );
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('code')
                    ->type('text')
                    ->placeholder(__('admin-currency.fields.code.placeholder'))
            )
                ->label(__('admin-currency.fields.code.label'))
                ->required()
                ->small(__('admin-currency.fields.code.help')),

            LayoutFactory::field(
                Input::make('minimum_value')
                    ->type('number')
                    ->placeholder(__('admin-currency.fields.minimum_value.placeholder'))
            )
                ->label(__('admin-currency.fields.minimum_value.label'))
                ->required()
                ->small(__('admin-currency.fields.minimum_value.help')),

            LayoutFactory::field(
                Input::make('exchange_rate')
                    ->type('number')
                    ->step('0.0001')
                    ->placeholder(__('admin-currency.fields.rate.placeholder'))
            )
                ->label(__('admin-currency.fields.rate.label'))
                ->required()
                ->small(__('admin-currency.fields.rate.help')),

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

        $validation = $this->validate([
            'code' => ['required', 'string', 'unique:currencies,code', 'size:3'],
            'minimum_value' => ['required', 'numeric', 'min:0'],
            'exchange_rate' => ['required', 'numeric', 'min:0'],
        ], $data);

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

        $currency = new Currency();
        $currency->code = strtoupper($data['code']);
        $currency->minimum_value = $data['minimum_value'];
        $currency->exchange_rate = $data['exchange_rate'];
        $currency->save();

        if (!empty($selectedPaymentGateways)) {
            foreach ($selectedPaymentGateways as $pgId) {
                $paymentGateway = PaymentGateway::findByPK($pgId);
                if ($paymentGateway) {
                    $currency->addPayment($paymentGateway);
                }
            }
        }
        $currency->save();

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
            $this->flashMessage(__('admin-currency.messages.currency_not_found'), 'danger');

            return;
        }

        $paymentGateways = $this->paymentGateways;

        $paymentGatewayCheckboxes = [];
        foreach ($paymentGateways as $pg) {
            $isChecked = $currency->hasPayment($pg);
            $paymentGatewayCheckboxes[] = LayoutFactory::field(
                CheckBox::make("payment_gateways.{$pg->id}")
                    ->label($pg->name)
                    ->popover($pg->adapter)
                    ->value($isChecked)
            );
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('code')
                    ->type('text')
                    ->placeholder(__('admin-currency.fields.code.placeholder'))
                    ->value($currency->code)
            )
                ->label(__('admin-currency.fields.code.label'))
                ->required()
                ->small(__('admin-currency.fields.code.help')),

            LayoutFactory::field(
                Input::make('minimum_value')
                    ->type('number')
                    ->placeholder(__('admin-currency.fields.minimum_value.placeholder'))
                    ->value($currency->minimum_value)
            )
                ->label(__('admin-currency.fields.minimum_value.label'))
                ->required()
                ->small(__('admin-currency.fields.minimum_value.help')),

            LayoutFactory::field(
                Input::make('exchange_rate')
                    ->type('number')
                    ->step('0.0001')
                    ->placeholder(__('admin-currency.fields.rate.placeholder'))
                    ->value($currency->exchange_rate)
            )
                ->label(__('admin-currency.fields.rate.label'))
                ->required()
                ->small(__('admin-currency.fields.rate.help')),

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
            $this->flashMessage(__('admin-currency.messages.currency_not_found'), 'danger');

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

        $validation = $this->validate([
            'code' => ['required', 'string', "unique:currencies,code,{$currency->id}", 'size:3'],
            'minimum_value' => ['required', 'numeric', 'min:0'],
            'exchange_rate' => ['required', 'numeric', 'min:0'],
        ], $data);

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
        $currency->exchange_rate = $data['exchange_rate'];
        $currency->save();

        $currency->clearPayments();

        if (!empty($selectedPaymentGateways)) {
            foreach ($selectedPaymentGateways as $pgId) {
                $paymentGateway = PaymentGateway::findByPK($pgId);
                if ($paymentGateway) {
                    $currency->addPayment($paymentGateway);
                }
            }
        }

        $currency->save();

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
            $this->flashMessage(__('admin-currency.messages.currency_not_found'), 'danger');

            return;
        }

        // Очищаем связи с платежными системами перед удалением
        $currency->clearPayments();
        $currency->save();

        $currency->delete();
        $this->flashMessage(__('admin-currency.messages.delete_success'), 'success');
        $this->currencies = rep(Currency::class)->select()->orderBy('id', 'desc');
    }
}
