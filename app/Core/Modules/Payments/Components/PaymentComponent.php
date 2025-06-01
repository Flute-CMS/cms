<?php

namespace Flute\Core\Modules\Payments\Components;

use Flute\Core\Database\Entities\Currency;
use Flute\Core\Modules\Payments\Exceptions\PaymentException;
use Flute\Core\Modules\Payments\Exceptions\PaymentPromoException;
use Flute\Core\Support\FluteComponent;
use Nette\Schema\ValidationException;

class PaymentComponent extends FluteComponent
{
    public ?string $gateway = null;
    public ?string $currency = null;
    public $amount = null;
    public $amountToReceive = null;
    public $amountToPay = null;
    public ?string $promoCode = null;

    public bool $agree = false;
    public bool $promoIsValid = true;

    public array $promoDetails = [];
    public array $currencies = [];
    public array $currencyExchangeRates = [];
    public array $currencyGateways = [];
    public array $currencyMinimumAmounts = [];

    public function mount()
    {
        $this->loadCurrenciesAndGateways();
        $this->setDefaultCurrencyAndGateway();
        $this->validatePromo();
        $this->validateCurrencyGateways();
    }

    protected function validateCurrencyGateways() : void
    {
        if (empty($this->currencyGateways)) {
            $this->amount = null;
            $this->amountToReceive = null;
            $this->amountToPay = null;
            $this->promoCode = null;
            $this->promoIsValid = true;
            $this->agree = false;
            $this->gateway = null;
        }
    }
    protected function loadCurrenciesAndGateways() : void
    {
        $currencies = Currency::findAll();

        foreach ($currencies as $currency) {
            $code = $currency->code;
            $this->currencies[] = $code;
            $this->currencyExchangeRates[$code] = $currency->exchange_rate;
            $this->currencyMinimumAmounts[$code] = $currency->minimum_value;

            foreach ($currency->paymentGateways as $gateway) {
                if ($gateway->enabled === false)
                    continue;

                $this->currencyGateways[$code][$gateway->adapter] = [
                    'name' => $gateway->name,
                    'image' => $gateway->image,
                ];
            }
        }
    }

    protected function setDefaultCurrencyAndGateway() : void
    {
        if (count($this->currencies) === 1) {
            $this->currency = $this->currencies[0];
        }

        if(!$this->currency) {
            $this->currency = $this->currencies[0];
        }

        if ($this->currency && isset($this->currencyGateways[$this->currency])) {
            $gateways = array_keys($this->currencyGateways[$this->currency]);
            if (count($gateways) === 1) {
                $this->gateway = $gateways[0];
            }

            if(!$this->gateway) {
                $this->gateway = $gateways[0];
            }
        }
    }

    public function purchase()
    {
        if ($this->validateInput()) {
            try {
                $this->throttle('lk_purchase');

                if ($this->amount < $this->currencyMinimumAmounts[$this->currency]) {
                    throw new PaymentException(__('lk.min_amount', ['sum' => $this->currencyMinimumAmounts[$this->currency]]));
                }

                $invoice = payments()->processor()->createInvoice(
                    $this->gateway,
                    $this->amountToPay,
                    (string) $this->promoCode,
                    $this->currency
                );

                toast()->success(__('lk.redirect'))->push();

                return $this->redirectTo(url("/payment/{$invoice->transactionId}"));
            } catch (PaymentPromoException $e) {
                $this->inputError('promoCode', $e->getMessage());
            } catch (ValidationException $e) {
                foreach ($e->getMessageObjects() as $error) {
                    toast()->error(__($error->code, $error->variables))->push();
                }
            } catch (PaymentException $e) {
                $this->inputError('amount', $e->getMessage());
            }
        }
    }

    public function validatePromo()
    {
        if (empty($this->amount)) {
            $this->amountToPay = null;
            $this->amountToReceive = null;
            return;
        }

        $this->amount = (float) $this->amount;

        if (isset($this->currencyExchangeRates[$this->currency])) {
            $exchangeRate = $this->currencyExchangeRates[$this->currency];
            $this->amountToPay = $this->amount;
            $this->amountToReceive = $this->amount * $exchangeRate;
        } else {
            $this->inputError('currency', __('lk.select_currency_prompt'));
            return;
        }

        if (empty($this->promoCode)) {
            return;
        }

        try {
            $this->throttle('lk_validate_promo');

            $this->promoDetails = payments()->promo()->validate($this->promoCode);
            $this->promoIsValid = true;

            switch ($this->promoDetails['type']) {
                case 'amount':
                    $this->amountToReceive = round($this->amountToReceive + $this->promoDetails['value'], 2);
                    break;
                case 'percentage':
                    $discount = ($this->amountToPay * $this->promoDetails['value']) / 100;
                    $this->amountToPay = round($this->amountToPay - $discount, 2);
                    break;
            }
        } catch (PaymentPromoException $e) {
            $this->inputError('promoCode', __($e->getMessage()));
            $this->promoIsValid = false;
            $this->resetAmounts();
        } catch (\Exception $e) {
            logs()->error($e);

            $message = is_debug() ? ($e->getMessage() ?? __('def.unknown_error')) : __('def.unknown_error');
            $this->inputError('promoCode', $message);

            $this->promoIsValid = false;
            $this->resetAmounts();
        }
    }

    protected function resetAmounts() : void
    {
        $exchangeRate = $this->currencyExchangeRates[$this->currency] ?? 1;
        $this->amountToPay = $this->amount;
        $this->amountToReceive = $this->amount * $exchangeRate;
    }

    protected function validateInput()
    {
        return validator()->validate([
            'gateway' => $this->gateway,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'promoCode' => $this->promoCode,
        ], [
            'gateway' => 'required|string',
            'currency' => 'required|string',
            'amount' => 'required|numeric|gt:0|max:'.config('lk.max_single_amount', 1000000),
            'promoCode' => 'nullable|string',
        ]);
    }

    public function render()
    {
        return $this->view('flute::components.payments.payment-form');
    }
}
