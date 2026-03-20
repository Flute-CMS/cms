<?php

namespace Flute\Core\Modules\Payments\Controllers;

use Exception;
use Flute\Core\Modules\Payments\Exceptions\PaymentException;
use Flute\Core\Modules\Payments\Exceptions\PaymentPromoException;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;
use Nette\Schema\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;

class PaymentFormController extends BaseController
{
    public function validatePromo(FluteRequest $request): JsonResponse
    {
        try {
            $this->throttle('lk_validate_promo');

            logs()->debug('payments.form.validate_promo.start', [
                'is_ajax' => $request->isAjax(),
                'accept' => $request->headers->get('Accept'),
                'content_type' => $request->headers->get('Content-Type'),
            ]);

            $code = $request->input('promoCode');
            $amount = (float) $request->input('amount', 0);

            if (empty($code)) {
                return $this->json(['valid' => false, 'error' => __('lk.promo_is_empty')]);
            }

            // Use a high amount for pre-validation when user hasn't entered amount yet,
            // so minimum_amount check doesn't reject valid promo codes prematurely.
            $validateAmount = $amount > 0 ? $amount : PHP_FLOAT_MAX;

            $details = payments()->promo()->validate($code, user()->getCurrentUser()->id, $validateAmount);

            return $this->json([
                'valid' => true,
                'type' => $details['type'],
                'value' => $details['value'],
                'message' => $details['message'],
            ]);
        } catch (PaymentPromoException $e) {
            return $this->json(['valid' => false, 'error' => __($e->getMessage())]);
        } catch (Exception $e) {
            $message = is_debug() ? $e->getMessage() ?? __('def.unknown_error') : __('def.unknown_error');

            return $this->json(['valid' => false, 'error' => $message]);
        }
    }

    public function purchase(FluteRequest $request): JsonResponse
    {
        try {
            $this->throttle('lk_purchase');

            logs()->debug('payments.form.purchase.start', [
                'is_ajax' => $request->isAjax(),
                'accept' => $request->headers->get('Accept'),
                'content_type' => $request->headers->get('Content-Type'),
                'keys' => array_keys((array) $request->input()),
            ]);

            $gateway = $request->input('gateway');
            $currency = $request->input('currency');
            $amount = $request->input('amount');
            $promoCode = $request->input('promoCode', '');
            $agree = (bool) $request->input('agree', false);

            if (is_string($amount)) {
                $amount = str_replace([' ', "\u{00A0}"], '', $amount);
                $amount = str_replace(',', '.', $amount);
            }

            $valid = validator()->validate([
                'gateway' => $gateway,
                'currency' => $currency,
                'amount' => $amount,
                'promoCode' => $promoCode,
            ], [
                'gateway' => 'required|string',
                'currency' => 'required|string',
                'amount' => 'required|numeric|gt:0|max:' . config('lk.max_single_amount', 1000000),
                'promoCode' => 'nullable|string',
            ]);

            if (!$valid) {
                logs()->warning('payments.form.purchase.validation_failed', [
                    'gateway' => $gateway,
                    'currency' => $currency,
                    'amount' => $amount,
                ]);

                return $this->json(['error' => __('def.unknown_error')], 422);
            }

            if (config('lk.oferta_view') && !$agree) {
                logs()->warning('payments.form.purchase.agreement_required', [
                    'gateway' => $gateway,
                    'currency' => $currency,
                ]);

                return $this->json(['error' => __('lk.agree_terms')], 422);
            }

            $amount = (float) $amount;

            // Resolve minimum amount from currency/gateway
            $currencies = \Flute\Core\Database\Entities\Currency::findAll();
            $exchangeRate = 1;
            $minAmount = 0;

            foreach ($currencies as $cur) {
                if ($cur->code === $currency) {
                    $exchangeRate = $cur->exchange_rate;
                    $minAmount = $cur->minimum_value;

                    // Check gateway-specific minimum
                    foreach ($cur->paymentGateways as $gw) {
                        if ($gw->adapter === $gateway && $gw->minimumAmount !== null) {
                            $minAmount = $gw->minimumAmount;
                        }
                    }

                    break;
                }
            }

            if ($amount < $minAmount) {
                logs()->warning('payments.form.purchase.min_amount_failed', [
                    'gateway' => $gateway,
                    'currency' => $currency,
                    'amount' => $amount,
                    'min_amount' => $minAmount,
                ]);

                return $this->json(['error' => __('lk.min_amount', ['sum' => $minAmount])], 422);
            }

            // Validate promo code before creating invoice
            if (!empty($promoCode)) {
                try {
                    payments()->promo()->validate($promoCode, user()->getCurrentUser()->id, $amount);
                } catch (PaymentPromoException $e) {
                    return $this->json(['error' => __($e->getMessage())], 422);
                }
            }

            $invoice = payments()->processor()->createInvoice($gateway, $amount, (string) $promoCode, $currency);

            logs()->info('payments.form.purchase.invoice_created', [
                'gateway' => $gateway,
                'currency' => $currency,
                'amount' => $amount,
                'transaction_id' => $invoice->transactionId,
            ]);

            return $this->json([
                'redirect' => url("/payment/{$invoice->transactionId}")->get(),
                'message' => __('lk.redirect'),
            ]);
        } catch (PaymentPromoException $e) {
            logs()->warning('payments.form.purchase.promo_failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->json(['error' => $e->getMessage(), 'field' => 'promoCode'], 422);
        } catch (ValidationException $e) {
            $messages = [];
            foreach ($e->getMessageObjects() as $error) {
                $messages[] = __($error->code, $error->variables);
            }

            logs()->warning('payments.form.purchase.schema_validation_failed', [
                'messages' => $messages,
            ]);

            return $this->json(['error' => implode(', ', $messages)], 422);
        } catch (PaymentException $e) {
            logs()->warning('payments.form.purchase.payment_failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->json(['error' => $e->getMessage(), 'field' => 'amount'], 422);
        } catch (Exception $e) {
            logs()->error($e);
            $message = is_debug() ? $e->getMessage() : __('def.unknown_error');

            return $this->json(['error' => $message], 500);
        }
    }
}
