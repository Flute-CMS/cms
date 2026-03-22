<?php

namespace Flute\Core\Modules\Payments\Processors;

use DateTimeImmutable;
use Flute\Core\Database\Entities\Currency;
use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Database\Entities\PromoCode;
use Flute\Core\Database\Entities\PromoCodeUsage;
use Flute\Core\Database\Entities\User;
use Flute\Core\Services\BalanceHistoryMeta;
use Flute\Core\Services\BalanceHistoryService;
use Flute\Core\Modules\Payments\Events\AfterGatewayResponseEvent;
use Flute\Core\Modules\Payments\Events\AfterPaymentCreatedEvent;
use Flute\Core\Modules\Payments\Events\BeforeGatewayProcessingEvent;
use Flute\Core\Modules\Payments\Events\BeforeInvoiceCreatedEvent;
use Flute\Core\Modules\Payments\Events\BeforePaymentEvent;
use Flute\Core\Modules\Payments\Events\PaymentFailedEvent;
use Flute\Core\Modules\Payments\Events\PaymentSuccessEvent;
use Flute\Core\Modules\Payments\Exceptions\PaymentException;
use Flute\Core\Modules\Payments\Factories\GatewayFactory;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Throwable;

class PaymentProcessor
{
    protected GatewayFactory $gatewayFactory;

    protected EventDispatcher $dispatcher;

    /**
     * Initializes the PaymentProcessor with necessary dependencies.
     *
     * @param GatewayFactory   $gatewayFactory  Factory to create payment gateways.
     * @param EventDispatcher  $eventDispatcher Event dispatcher for handling events.
     */
    public function __construct(GatewayFactory $gatewayFactory, EventDispatcher $eventDispatcher)
    {
        $this->gatewayFactory = $gatewayFactory;
        $this->dispatcher = $eventDispatcher;
    }

    /**
     * Creates an invoice without processing payment immediately.
     *
     * @param string      $gatewayName  Name of the payment gateway.
     * @param int|float   $amount       Amount to be paid.
     * @param string|null $promo        Promo code, if any.
     * @param string|null $currencyCode Currency code, if any.
     *
     * @throws PaymentException
     * @return PaymentInvoice The created invoice.
     */
    public function createInvoice(
        string $gatewayName,
        $amount,
        ?string $promo = null,
        ?string $currencyCode = null,
    ): PaymentInvoice {
        $gateway = PaymentGateway::findOne([
            'adapter' => $gatewayName,
        ]);

        if (!$gateway) {
            throw new PaymentException("Gateway {$gatewayName} wasn't found");
        }

        if ($gateway->enabled == false) {
            throw new PaymentException("Gateway {$gatewayName} is disabled");
        }

        $this->dispatcher->dispatch(new BeforePaymentEvent($amount, $promo), BeforePaymentEvent::NAME);

        $event = $this->dispatcher->dispatch(
            new BeforeInvoiceCreatedEvent($gatewayName, $amount, $promo, $currencyCode, request()->input()),
            BeforeInvoiceCreatedEvent::NAME,
        );

        $amount = $event->getAmount();
        $promo = $event->getPromo();
        $currencyCode = $event->getCurrencyCode();

        if ($amount <= 0) {
            throw new PaymentException('Payment amount must be positive');
        }

        if ($amount > 10000000) {
            throw new PaymentException('Payment amount exceeds maximum limit');
        }

        $invoiceAmount = $amount;
        $user = user()->getCurrentUser();
        $currency = null;

        $invoice = new PaymentInvoice();

        if (!empty($event->getAdditionalData())) {
            $invoice->additional = json_encode($event->getAdditionalData());
        }

        if (!empty($currencyCode)) {
            $currency = $this->getCurrency($gateway, $invoice, $currencyCode);
            if ($currency) {
                $invoiceAmount = $this->convertAmountToCurrency($invoiceAmount, $currency);
            } else {
                throw new PaymentException("Currency {$currencyCode} is not supported by the gateway.");
            }
        }

        $invoice->originalAmount = $amount;
        $invoice->amount = $invoiceAmount;

        $invoice->promoCode = $promo ? payments()->promo()->get($promo) : null;
        $invoice->gateway = $gateway->adapter;
        $invoice->transactionId = $this->generateTransactionId();
        $invoice->isPaid = false;
        $invoice->user = $user;
        $invoice->currency = $currency;

        $this->dispatcher->dispatch(new AfterPaymentCreatedEvent($invoice), AfterPaymentCreatedEvent::NAME);

        transaction($invoice)->run();

        if (function_exists('notify')) {
            try {
                notify('core.invoice_created', $user, [
                    'amount' => number_format($invoice->originalAmount, 2),
                    'gateway' => $invoice->gateway,
                    'transaction_id' => $invoice->transactionId,
                ]);
            } catch (Throwable $e) {
                logs()->error('Notification [core.invoice_created] failed: ' . $e->getMessage());
            }
        }

        return $invoice;
    }

    /**
     * Handles the payment response from the gateway.
     *
     * @param string $gatewayName Name of the gateway.
     *
     * @throws PaymentException
     */
    public function handlePayment(string $gatewayName): void
    {
        if (!payments()->gatewayExists($gatewayName)) {
            throw new PaymentException('Gateway does not exist');
        }

        $gatewayEntity = PaymentGateway::findOne(['enabled' => true, 'adapter' => $gatewayName]);

        if (!$gatewayEntity) {
            throw new PaymentException("Gateway wasn't found");
        }

        logs()->debug('payments.processor.handle.start', [
            'gateway' => $gatewayName,
            'keys' => array_keys((array) request()->input()),
        ]);

        $gateway = $this->gatewayFactory->create($gatewayEntity);
        $response = $this->completePayment($gateway, request()->input());

        if ($response->isSuccessful()) {
            $this->processSuccessfulPayment($response);
            logs()->info('payments.processor.handle.success', [
                'gateway' => $gatewayName,
            ]);
        } else {
            $this->processFailedPayment($response);
        }
    }

    /**
     * Sets the invoice as paid within a single database transaction
     * to prevent race conditions (double-spend).
     *
     * @param string $transactionId Transaction ID.
     *
     * @throws PaymentException
     */
    public function setInvoiceAsPaid(string $transactionId, ?float $verifyAmount = null): void
    {
        $database = db();

        $database->begin();

        try {
            $invoice = PaymentInvoice::query()
                ->forUpdate()
                ->where(['transactionId' => $transactionId])
                ->fetchOne();

            if (!$invoice) {
                $database->rollback();

                throw new PaymentException("Invoice wasn't found");
            }

            if ($invoice->isPaid) {
                $database->rollback();

                throw new PaymentException('Invoice is already paid');
            }

            $gateway = PaymentGateway::findOne(['adapter' => $invoice->gateway]);

            // Use post-conversion amount for verification when currency conversion was applied
            $expectedAmount = $invoice->currency ? $invoice->amount : $invoice->originalAmount;

            // Gateway receives fee-inclusive amount, so adjust expected for verification
            $gatewayFee = $gateway && $gateway->fee > 0 ? $gateway->fee : 0;
            if ($gatewayFee > 0) {
                $expectedAmount = round($expectedAmount + ( ( $expectedAmount * $gatewayFee ) / 100 ), 2);
            }

            $tolerancePercent = min((float) config('lk.amount_tolerance_percent', 1), 5);
            $toleranceAbs = max(0.01, $expectedAmount * ( $tolerancePercent / 100 ));

            if ($expectedAmount > 0) {
                if ($verifyAmount === null) {
                    logs()->warning(
                        "Payment amount verification failed (null) for transaction {$transactionId}, expected {$expectedAmount}",
                    );

                    $database->rollback();

                    throw new PaymentException(
                        "Amount verification failed: gateway did not return amount for transaction {$transactionId}",
                    );
                } else {
                    $minAllowed = max(0.0, $expectedAmount - $toleranceAbs);

                    if ($verifyAmount < $minAllowed) {
                        $database->rollback();

                        throw new PaymentException(
                            "Amount too low: expected at least {$minAllowed}, received {$verifyAmount}",
                        );
                    }

                    if (abs($verifyAmount - $expectedAmount) > $toleranceAbs) {
                        logs()->info('payments.amount.difference', [
                            'transaction_id' => $transactionId,
                            'expected' => $expectedAmount,
                            'received' => $verifyAmount,
                        ]);
                    }
                }
            }

            $user = user()->get($invoice->user->id);

            $promo = $invoice->promoCode;
            $amount = $invoice->amount;

            $promoBonus = 0;
            $gatewayBonus = 0;

            if ($promo) {
                $promoData = payments()->promo()->validate($promo->code, $user->id, $invoice->originalAmount, true);
                $promoBonus = $this->calculatePromoBonus($promoData, $invoice->originalAmount);
            }

            if ($gateway && $gateway->bonus > 0) {
                $gatewayBonus = round(( $amount * $gateway->bonus ) / 100, 2);
            }

            $this->dispatcher->dispatch(new PaymentSuccessEvent($invoice, $user), PaymentSuccessEvent::NAME);
            $invoice->isPaid = true;
            $invoice->paidAt = new DateTimeImmutable();
            transaction($invoice)->run();

            $totalAmount = $amount + $promoBonus + $gatewayBonus;

            if ($promo) {
                $usage = new PromoCodeUsage();
                $usage->promoCode = $promo;
                $usage->invoice = $invoice;
                $usage->user = $user;
                $usage->used_at = new DateTimeImmutable();
                transaction($usage)->run();
            }

            // topup within the same DB transaction
            $balanceUser = User::query()
                ->forUpdate()
                ->where(['id' => $user->id])
                ->fetchOne();

            $balanceUser->balance += $totalAmount;
            transaction($balanceUser)->run();

            $database->commit();

            try {
                $meta = BalanceHistoryMeta::make()->invoiceId($invoice->id);
                if ($promo) {
                    $meta->promoCodeId($promo->id);
                }
                if ($promoBonus > 0 || $gatewayBonus > 0) {
                    $meta->set('promo_bonus', $promoBonus)->set('gateway_bonus', $gatewayBonus);
                }

                app(BalanceHistoryService::class)->topup(
                    $balanceUser,
                    $totalAmount,
                    $balanceUser->balance,
                    'payment',
                    $gateway ? $gateway->name : null,
                    $meta,
                );
            } catch (Throwable $e) {
                logs()->error('Balance history record failed: ' . $e->getMessage());
            }

            // Notifications outside transaction
            if (function_exists('notify')) {
                try {
                    notify('core.balance_topup', $balanceUser, [
                        'amount' => number_format($totalAmount, 2),
                        'balance' => number_format($balanceUser->balance, 2),
                    ]);
                } catch (Throwable $e) {
                    logs()->error('Notification [core.balance_topup] failed: ' . $e->getMessage());
                }
            }
        } catch (PaymentException $e) {
            throw $e;
        } catch (Throwable $e) {
            $database->rollback();

            throw new PaymentException('Payment processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Calculates the promo bonus based on promo data.
     *
     * @param array $promoData Promo data array.
     * @param float $amount    Original amount.
     *
     * @return float Calculated promo bonus.
     */
    public function calculatePromoBonus(array $promoData, $amount): float
    {
        return match ($promoData['type']) {
            'percentage' => (float) $amount * ( (float) ( $promoData['value'] ?? 0 ) / 100.0 ),
            'amount' => (float) $promoData['value'],
            default => 0,
        };
    }

    /**
     * Records the usage of a promo code.
     *
     * @param PromoCode      $promo   Promo code entity.
     * @param User           $user    User entity.
     * @param PaymentInvoice $invoice Invoice entity.
     */
    public function recordPromoUsage(PromoCode $promo, User $user, PaymentInvoice $invoice): void
    {
        $usage = new PromoCodeUsage();
        $usage->promoCode = $promo;
        $usage->invoice = $invoice;
        $usage->user = $user;
        $usage->used_at = new DateTimeImmutable();
        $usage->saveOrFail();
    }

    /**
     * Processes the payment by initializing the gateway and redirecting the user.
     *
     * @param PaymentInvoice  $invoice       Payment invoice entity.
     * @param PaymentGateway  $gatewayEntity Payment gateway entity.
     *
     * @throws PaymentException
     */
    public function processPayment(PaymentInvoice $invoice, PaymentGateway $gatewayEntity)
    {
        $gateway = $this->gatewayFactory->create($gatewayEntity);

        $gatewayAmount = $invoice->currency ? $invoice->amount : $invoice->originalAmount;
        if ($gatewayEntity->fee > 0) {
            $gatewayAmount = round($gatewayAmount + ( ( $gatewayAmount * $gatewayEntity->fee ) / 100 ), 2);
        }

        $additional = \Nette\Utils\Json::decode($gatewayEntity->additional, \Nette\Utils\Json::FORCE_ARRAY);

        foreach ($additional as $key => $val) {
            $additional[$key] = str_replace(
                ['{{amount}}', '{{transactionId}}', '{{currency}}'],
                [$gatewayAmount, $invoice->transactionId, $invoice->currency->code ?? ''],
                $val,
            );
        }

        $paymentData = array_merge([
            'amount' => $gatewayAmount,
            'transactionId' => (string) $invoice->transactionId,
            'cancelUrl' => url('/lk/fail')->get(),
            'returnUrl' => url('/lk/success')->get(),
        ], $additional);

        if (!isset($paymentData['currency'])) {
            $paymentData['currency'] = $invoice->currency->code ?? 'RUB';
        }

        if (method_exists($gateway, 'getTestMode')) {
            $paymentData['testMode'] = $gateway->getTestMode();
        }

        if (isset($paymentData['keys'])) {
            foreach ($paymentData['keys'] as $key => $value) {
                $paymentData[$key] = $value;
            }
            unset($paymentData['keys']);
        }

        $event = $this->dispatcher->dispatch(
            new BeforeGatewayProcessingEvent($invoice, $gatewayEntity, $gateway, $paymentData),
            BeforeGatewayProcessingEvent::NAME,
        );

        $paymentData = $event->getPaymentData();
        $gateway = $event->getGateway();
        $gatewayEntity = $event->getPaymentGateway();
        $invoice = $event->getInvoice();

        $paymentData['notifyUrl'] = url('/api/lk/handle/' . $gatewayEntity->adapter)->get();

        $response = $gateway->purchase($paymentData)->send();

        $this->dispatcher->dispatch(
            new AfterGatewayResponseEvent($invoice, $response),
            AfterGatewayResponseEvent::NAME,
        );

        if ($response->isRedirect() && $response instanceof RedirectResponseInterface) {
            // Some gateways use POST-redirect (form submission) instead of simple 302.
            // Detect this and let Omnipay handle it natively when needed.
            if ($response->getRedirectMethod() !== 'GET') {
                $response->redirect();

                return null;
            }

            $redirectUrl = $response->getRedirectUrl();
            if (empty($redirectUrl)) {
                throw new PaymentException('Gateway returned empty redirect URL');
            }

            return response()->redirect($redirectUrl);
        }

        throw new PaymentException($response->getMessage() ?? $response->getData()['message'] ?? 'Unknown error');
    }

    /**
     * Generates a cryptographically secure unique transaction ID.
     *
     * @return string Unique transaction ID.
     */
    protected function generateTransactionId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Converts the amount to the specified currency.
     *
     * @param float    $amount   Amount to convert.
     * @param Currency $currency Currency entity.
     *
     * @return float Converted amount.
     */
    protected function convertAmountToCurrency($amount, Currency $currency): float
    {
        return $amount * $currency->exchange_rate;
    }

    /**
     * Retrieves the currency entity and assigns it to the invoice.
     *
     * @param PaymentGateway  $gateway        Payment gateway entity.
     * @param PaymentInvoice  $paymentInvoice Payment invoice entity.
     * @param string          $code           Currency code.
     *
     * @return Currency|null Currency entity or null if not found.
     */
    protected function getCurrency(PaymentGateway $gateway, PaymentInvoice $paymentInvoice, string $code): ?Currency
    {
        $currency = Currency::findOne(['code' => $code]);

        if (empty($currency) || !$currency->hasPayment($gateway)) {
            return null;
        }

        $paymentInvoice->currency = $currency;

        return $currency;
    }

    /**
     * Completes the payment process with the gateway.
     *
     * @param mixed $gateway Gateway instance.
     * @param array $input   Input data from the request.
     *
     * @throws PaymentException
     * @return mixed Response from the gateway.
     */
    protected function completePayment($gateway, $input)
    {
        if (method_exists($gateway, 'acceptNotification')) {
            return $gateway->acceptNotification()->send();
        } elseif (method_exists($gateway, 'completePurchase')) {
            if (!isset($input['transactionReference'])) {
                if (isset($input['paymentId'])) {
                    $input['transactionReference'] = $input['paymentId'];
                } elseif (isset($input['PaymentID'])) {
                    $input['transactionReference'] = $input['PaymentID'];
                } elseif (isset($input['paymentID'])) {
                    $input['transactionReference'] = $input['paymentID'];
                } elseif (isset($input['PAYMENTID'])) {
                    $input['transactionReference'] = $input['PAYMENTID'];
                } elseif (isset($input['transaction_id'])) {
                    $input['transactionReference'] = $input['transaction_id'];
                } elseif (isset($input['transactionId'])) {
                    $input['transactionReference'] = $input['transactionId'];
                } elseif (isset($input['order_id'])) {
                    $input['transactionReference'] = $input['order_id'];
                } elseif (isset($input['orderId'])) {
                    $input['transactionReference'] = $input['orderId'];
                } elseif (isset($input['invoice_id'])) {
                    $input['transactionReference'] = $input['invoice_id'];
                } elseif (isset($input['invoiceId'])) {
                    $input['transactionReference'] = $input['invoiceId'];
                }
            }
            if (!isset($input['payerId'])) {
                if (isset($input['PayerID'])) {
                    $input['payerId'] = $input['PayerID'];
                } elseif (isset($input['payerID'])) {
                    $input['payerId'] = $input['payerID'];
                } elseif (isset($input['PAYERID'])) {
                    $input['payerId'] = $input['PAYERID'];
                }
            }

            return $gateway->completePurchase($input)->send();
        } elseif (method_exists($gateway, 'notification')) {
            return $gateway->notification(['request' => $input])->send();
        }

        throw new PaymentException('Unsupported gateway');
    }

    /**
     * Processes a successful payment.
     *
     * @param mixed $response Response from the gateway.
     *
     * @throws PaymentException
     */
    protected function processSuccessfulPayment($response): void
    {
        $transactionId = trim((string) $response->getTransactionId());

        if ($transactionId === '') {
            $responseData = method_exists($response, 'getData') ? (array) $response->getData() : [];
            $transactionId = $this->resolveTransactionId(request()->input(), $responseData);
        }

        if ($transactionId === '') {
            throw new PaymentException('Unable to resolve transaction id from callback payload');
        }

        $paidAmount = null;
        if (method_exists($response, 'getAmount')) {
            $rawAmount = $response->getAmount();
            if ($rawAmount !== null && is_scalar($rawAmount)) {
                $normalized = str_replace(',', '.', (string) $rawAmount);
                if (preg_match('/-?\d+(?:\.\d+)?/', $normalized, $matches)) {
                    $paidAmount = (float) $matches[0];
                }
            }
        }

        $this->setInvoiceAsPaid($transactionId, $paidAmount);
    }

    /**
     * Processes a failed payment.
     *
     * @param ResponseInterface $response Response from the gateway.
     *
     * @throws PaymentException
     */
    protected function processFailedPayment(ResponseInterface $response): void
    {
        $this->dispatcher->dispatch(new PaymentFailedEvent($response), PaymentFailedEvent::NAME);

        throw new PaymentException($response->getMessage());
    }

    /**
     * Resolve internal invoice transaction id from request/response payload.
     */
    private function resolveTransactionId(array $input, array $responseData = []): string
    {
        $candidateKeys = [
            'transactionId',
            'transaction_id',
            'transactionReference',
            'transaction_reference',
            'order_id',
            'orderId',
            'merchant_order_id',
            'merchantOrderId',
            'invoice',
            'invoice_id',
            'invoiceId',
            'inv_id',
            'payment_id',
            'paymentId',
        ];

        $sources = [$input, $responseData];

        foreach ($sources as $source) {
            foreach ($candidateKeys as $key) {
                if (!array_key_exists($key, $source)) {
                    continue;
                }

                $value = $source[$key];
                if (!is_scalar($value)) {
                    continue;
                }

                $candidate = trim((string) $value);
                if ($candidate === '') {
                    continue;
                }

                if (PaymentInvoice::findOne(['transactionId' => $candidate])) {
                    return $candidate;
                }

                if (preg_match('/\d{8,}/', $candidate, $matches)) {
                    $digitsCandidate = $matches[0];
                    if (PaymentInvoice::findOne(['transactionId' => $digitsCandidate])) {
                        return $digitsCandidate;
                    }
                }
            }

            $lookupCount = 0;
            foreach ($source as $value) {
                $resolved = $this->resolveTransactionIdFromValue($value, $lookupCount);
                if ($resolved !== '') {
                    return $resolved;
                }
            }
        }

        return '';
    }

    /**
     * Best-effort recursive extraction for unknown gateway payload shapes.
     * Limited to 20 DB lookups to prevent DoS.
     */
    private function resolveTransactionIdFromValue($value, int &$lookupCount): string
    {
        if ($lookupCount >= 20) {
            return '';
        }

        if (is_array($value)) {
            foreach ($value as $nested) {
                $resolved = $this->resolveTransactionIdFromValue($nested, $lookupCount);
                if ($resolved !== '') {
                    return $resolved;
                }
            }

            return '';
        }

        if (!is_scalar($value)) {
            return '';
        }

        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        $lookupCount++;
        if (PaymentInvoice::findOne(['transactionId' => $text])) {
            return $text;
        }

        if (preg_match_all('/\d{8,}/', $text, $matches)) {
            foreach ($matches[0] as $digitsCandidate) {
                $lookupCount++;
                if ($lookupCount >= 20) {
                    return '';
                }

                if (PaymentInvoice::findOne(['transactionId' => $digitsCandidate])) {
                    return $digitsCandidate;
                }
            }
        }

        return '';
    }
}
