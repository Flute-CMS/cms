<?php

namespace Flute\Core\Modules\Payments\Components;

use Exception;
use Flute\Core\Database\Entities\Currency;
use Flute\Core\Modules\Payments\Exceptions\PaymentException;
use Flute\Core\Modules\Payments\Exceptions\PaymentPromoException;
use Flute\Core\Modules\Payments\Exceptions\PaymentValidationException;
use Flute\Core\Support\FluteComponent;
use Nette\Schema\ValidationException;

class PaymentComponent extends FluteComponent
{
    public ?string $gateway = null;

    public ?string $currency = null;

    public $amount = null;

    public $amountToReceive = null;

    public $amountToPay = null;

    public $gatewayFee = 0;

    public $gatewayFeeAmount = 0;

    public $gatewayBonus = 0;

    public $gatewayBonusAmount = 0;

    public ?string $promoCode = null;

    public bool $agree = false;

    public bool $isModal = false;

    public bool $promoIsValid = true;

    public array $promoDetails = [];

    public array $currencies = [];

    public array $currencyExchangeRates = [];

    public array $currencyGateways = [];

    public array $currencyMinimumAmounts = [];

    public array $gatewayMinimumAmounts = [];

    public function mount()
    {
        $this->loadCurrenciesAndGateways();
        $this->setDefaultCurrencyAndGateway();
        $this->validatePromo();
        $this->validateCurrencyGateways();
    }

    public function purchase()
    {
        if ($this->validateInput()) {
            try {
                $this->throttle('lk_purchase');

                $minAmount = $this->getEffectiveMinimumAmount();
                if ($this->amount < $minAmount) {
                    throw new PaymentException(__('lk.min_amount', ['sum' => $minAmount]));
                }

                $serverAmount = (float) $this->amount;
                $serverAmountToPay = $serverAmount;

                if (!empty($this->promoCode)) {
                    try {
                        $promoDetails = payments()->promo()->validate($this->promoCode, user()->getCurrentUser()->id, $serverAmount);
                        if ($promoDetails['type'] === 'percentage') {
                            $discount = ($serverAmountToPay * $promoDetails['value']) / 100;
                            $serverAmountToPay = round(max(0, $serverAmountToPay - $discount), 2);
                        }
                    } catch (\Flute\Core\Modules\Payments\Exceptions\PaymentPromoException $e) {
                        $this->inputError('promoCode', $e->getMessage());

                        return;
                    }
                }

                $invoice = payments()->processor()->createInvoice(
                    $this->gateway,
                    $serverAmountToPay,
                    (string) $this->promoCode,
                    $this->currency
                );

                // If invoice amount is zero (promo covered full sum) — mark as paid immediately,
                // credit user's balance and record promo usage without redirecting to gateway.
                if ($invoice->amount <= 0) {
                    payments()->processor()->setInvoiceAsPaid($invoice->transactionId);
                    toast()->success(__('lk.success'))->push();

                    return $this->redirectTo(url('/lk/success'));
                }

                toast()->success(__('lk.redirect'))->push();

                return $this->redirectTo(url("/payment/{$invoice->transactionId}"));
            } catch (PaymentPromoException $e) {
                $this->inputError('promoCode', $e->getMessage());
            } catch (PaymentValidationException $e) {
                $this->inputError($e->getField(), $e->getMessage());
            } catch (ValidationException $e) {
                foreach ($e->getMessageObjects() as $error) {
                    toast()->error(__($error->code, $error->variables))->push();
                }
            } catch (PaymentException $e) {
                $this->inputError('amount', $e->getMessage());
            }
        }
    }

    public function setPresetAmount(float $amount): void
    {
        $this->amount = $amount;
        $this->validatePromo();
    }

    public function validatePromo()
    {
        if (is_string($this->amount)) {
            $normalized = str_replace([' ', "\u{00A0}"], '', $this->amount);
            $normalized = str_replace(',', '.', $normalized);
            $this->amount = $normalized;
        }

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

            $this->gatewayFee = 0;
            $this->gatewayFeeAmount = 0;
            $this->gatewayBonus = 0;
            $this->gatewayBonusAmount = 0;

            if ($this->gateway && isset($this->currencyGateways[$this->currency][$this->gateway])) {
                $gatewayData = $this->currencyGateways[$this->currency][$this->gateway];
                $this->gatewayFee = $gatewayData['fee'] ?? 0;
                $this->gatewayBonus = $gatewayData['bonus'] ?? 0;

                if ($this->gatewayFee > 0) {
                    $this->gatewayFeeAmount = round(($this->amount * $this->gatewayFee) / 100, 2);
                }

                if ($this->gatewayBonus > 0) {
                    $this->gatewayBonusAmount = round(($this->amountToReceive * $this->gatewayBonus) / 100, 2);
                    $this->amountToReceive = round($this->amountToReceive + $this->gatewayBonusAmount, 2);
                }
            }
        } else {
            $this->inputError('currency', __('lk.select_currency_prompt'));

            return;
        }

        if (empty($this->promoCode)) {
            if ($this->gatewayFee > 0) {
                $feeOnPay = round(($this->amountToPay * $this->gatewayFee) / 100, 2);
                $this->amountToPay = round($this->amountToPay + $feeOnPay, 2);
            }

            return;
        }

        try {
            $this->throttle('lk_validate_promo');

            $this->promoDetails = payments()->promo()->validate($this->promoCode, user()->getCurrentUser()->id, $this->amount);
            $this->promoIsValid = true;

            switch ($this->promoDetails['type']) {
                case 'amount':
                    $this->amountToReceive = round($this->amountToReceive + $this->promoDetails['value'], 2);

                    break;
                case 'percentage':
                    $discount = ($this->amountToPay * $this->promoDetails['value']) / 100;
                    $this->amountToPay = round(max(0, $this->amountToPay - $discount), 2);

                    break;
            }

            if ($this->gatewayFee > 0) {
                $feeOnPay = round(($this->amountToPay * $this->gatewayFee) / 100, 2);
                $this->amountToPay = round($this->amountToPay + $feeOnPay, 2);
            }
        } catch (PaymentPromoException $e) {
            $this->inputError('promoCode', __($e->getMessage()));
            $this->promoIsValid = false;
            $this->resetAmounts();
        } catch (Exception $e) {
            logs()->error($e);

            $message = is_debug() ? ($e->getMessage() ?? __('def.unknown_error')) : __('def.unknown_error');
            $this->inputError('promoCode', $message);

            $this->promoIsValid = false;
            $this->resetAmounts();
        }
    }

    public function render()
    {
        $viewName = $this->isModal
            ? 'flute::components.payments.payment-form-modal'
            : 'flute::components.payments.payment-form';

        return $this->view($viewName, [
            'gatewayFields' => $this->collectAllGatewayFields($viewName),
        ]);
    }

    public function getEffectiveMinimumAmount(): float
    {
        // Gateway minimum takes priority if set
        if ($this->gateway && isset($this->currencyGateways[$this->currency][$this->gateway])) {
            $gatewayData = $this->currencyGateways[$this->currency][$this->gateway];
            if (isset($gatewayData['minimum_amount']) && $gatewayData['minimum_amount'] !== null) {
                return (float) $gatewayData['minimum_amount'];
            }
        }

        // Fall back to currency minimum
        return (float) ($this->currencyMinimumAmounts[$this->currency] ?? 0);
    }

    /**
     * Pre-render additional fields for ALL gateways by triggering View Composers.
     * Modules register View::composer on the payment form view and conditionally
     * inject 'additionalFields' based on $view->gateway.
     */
    protected function collectAllGatewayFields(string $viewName): array
    {
        $fields = [];
        $seen = [];

        foreach ($this->currencyGateways as $gateways) {
            foreach ($gateways as $adapter => $data) {
                if (isset($seen[$adapter])) {
                    continue;
                }
                $seen[$adapter] = true;

                try {
                    $factory = view();
                    $probe = $factory->make($viewName, ['gateway' => $adapter]);
                    $factory->callComposer($probe);

                    $html = $probe->getData()['additionalFields'] ?? null;
                    if ($html) {
                        $fields[$adapter] = $html;
                    }
                } catch (Exception $e) {
                    // Skip gateways that fail to render fields
                }
            }
        }

        return $fields;
    }

    protected function validateCurrencyGateways(): void
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

    protected function loadCurrenciesAndGateways(): void
    {
        $currencies = Currency::findAll();

        foreach ($currencies as $currency) {
            $code = $currency->code;
            $this->currencies[] = $code;
            $this->currencyExchangeRates[$code] = $currency->exchange_rate;
            $this->currencyMinimumAmounts[$code] = $currency->minimum_value;

            foreach ($currency->paymentGateways as $gateway) {
                if ($gateway->enabled === false) {
                    continue;
                }

                $this->currencyGateways[$code][$gateway->adapter] = [
                    'name' => $gateway->name,
                    'image' => $gateway->image,
                    'description' => $gateway->description ?? '',
                    'fee' => $gateway->fee ?? 0,
                    'bonus' => $gateway->bonus ?? 0,
                    'minimum_amount' => $gateway->minimumAmount,
                ];
            }
        }
    }

    protected function setDefaultCurrencyAndGateway(): void
    {
        if (count($this->currencies) === 1) {
            $this->currency = $this->currencies[0];
        }

        if (!$this->currency) {
            $this->currency = $this->currencies[0];
        }

        if ($this->currency && isset($this->currencyGateways[$this->currency])) {
            $gateways = array_keys($this->currencyGateways[$this->currency]);
            if (count($gateways) === 1) {
                $this->gateway = $gateways[0];
            }

            if (!$this->gateway) {
                $this->gateway = $gateways[0];
            }
        }
    }

    protected function resetAmounts(): void
    {
        $exchangeRate = $this->currencyExchangeRates[$this->currency] ?? 1;
        $this->amountToPay = $this->amount;
        $this->amountToReceive = $this->amount * $exchangeRate;

        $this->gatewayFee = 0;
        $this->gatewayFeeAmount = 0;
        $this->gatewayBonus = 0;
        $this->gatewayBonusAmount = 0;

        if ($this->gateway && isset($this->currencyGateways[$this->currency][$this->gateway])) {
            $gatewayData = $this->currencyGateways[$this->currency][$this->gateway];
            $this->gatewayFee = $gatewayData['fee'] ?? 0;
            $this->gatewayBonus = $gatewayData['bonus'] ?? 0;

            if ($this->gatewayFee > 0) {
                $this->gatewayFeeAmount = round(($this->amount * $this->gatewayFee) / 100, 2);
                $feeOnPay = round(($this->amountToPay * $this->gatewayFee) / 100, 2);
                $this->amountToPay = round($this->amountToPay + $feeOnPay, 2);
            }

            if ($this->gatewayBonus > 0) {
                $this->gatewayBonusAmount = round(($this->amountToReceive * $this->gatewayBonus) / 100, 2);
                $this->amountToReceive = round($this->amountToReceive + $this->gatewayBonusAmount, 2);
            }
        }
    }

    protected function validateInput()
    {
        if (is_string($this->amount)) {
            $normalized = str_replace([' ', "\u{00A0}"], '', $this->amount);
            $normalized = str_replace(',', '.', $normalized);
            $this->amount = $normalized;
        }

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
}
