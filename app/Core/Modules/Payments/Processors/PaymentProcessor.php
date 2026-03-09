<?php

namespace Flute\Core\Modules\Payments\Processors;

use DateTimeImmutable;
use Flute\Core\Database\Entities\Currency;
use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Database\Entities\PromoCode;
use Flute\Core\Database\Entities\PromoCodeUsage;
use Flute\Core\Database\Entities\User;
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
    public function createInvoice(string $gatewayName, $amount, ?string $promo = null, ?string $currencyCode = null): PaymentInvoice
    {
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

        $event = $this->dispatcher->dispatch(new BeforeInvoiceCreatedEvent($gatewayName, $amount, $promo, $currencyCode, request()->input()), BeforeInvoiceCreatedEvent::NAME);

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
        $invoice->currency = $currency ?? null;

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

        $gateway = $this->gatewayFactory->create($gatewayEntity);
        $response = $this->completePayment($gateway, request()->input());

        if ($response->isSuccessful()) {
            $this->processSuccessfulPayment($response);
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
            $invoice = PaymentInvoice::query()->forUpdate()->where(['transactionId' => $transactionId])->fetchOne();

            if (!$invoice) {
                $database->rollback();

                throw new PaymentException("Invoice wasn't found");
            }

            if ($invoice->isPaid) {
                $database->rollback();

                throw new PaymentException("Invoice is already paid");
            }

            $gateway = PaymentGateway::findOne(['adapter' => $invoice->gateway]);

            // Use post-conversion amount for verification when currency conversion was applied
            $expectedAmount = $invoice->currency ? $invoice->amount : $invoice->originalAmount;

            $tolerancePercent = min((float) config('lk.amount_tolerance_percent', 1), 5);
            $gatewayFee = ($gateway && $gateway->fee > 0) ? $gateway->fee : 0;
            $effectiveTolerance = max($tolerancePercent, $gatewayFee);
            $toleranceAbs = max(0.01, $expectedAmount * ($effectiveTolerance / 100));

            if ($expectedAmount > 0) {
                if ($verifyAmount === null) {
                    logs()->warning("Payment amount verification skipped (null) for transaction {$transactionId}, expected {$expectedAmount}");
                } elseif (abs($verifyAmount - $expectedAmount) > $toleranceAbs) {
                    $database->rollback();

                    throw new PaymentException("Amount mismatch: expected {$expectedAmount}, received {$verifyAmount}");
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
                $gatewayBonus = round(($amount * $gateway->bonus) / 100, 2);
            }

            $this->dispatcher->dispatch(new PaymentSuccessEvent($invoice, $user), PaymentSuccessEvent::NAME);
            $invoice->isPaid = true;
            $invoice->paidAt = new DateTimeImmutable();
            transaction($invoice)->run();

            $totalAmount = $amount + $promoBonus + $gatewayBonus;

            if ($promo) {
                $this->recordPromoUsage($promo, $user, $invoice);
            }

            // topup within the same DB transaction
            $balanceUser = User::query()
                ->forUpdate()
                ->where(['id' => $user->id])
                ->fetchOne();

            $balanceUser->balance += $totalAmount;
            transaction($balanceUser)->run();

            $database->commit();

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
        switch ($promoData['type']) {
            case 'percentage':
                $percentage = (float) ($promoData['value'] ?? 0);
                if ($percentage <= 0) {
                    return 0;
                }

                return (float) $amount * ($percentage / 100.0);

            case 'amount':
                return $promoData['value'];

            default:
                return 0;
        }
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
        transaction($usage)->run();
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

        $additional = \Nette\Utils\Json::decode($gatewayEntity->additional, \Nette\Utils\Json::FORCE_ARRAY);

        foreach ($additional as $key => $val) {
            $additional[$key] = str_replace(
                ["{{amount}}", "{{transactionId}}", "{{currency}}"],
                [$invoice->originalAmount, $invoice->transactionId, $invoice->currency->code ?? ''],
                $val
            );
        }

        $paymentData = array_merge([
            'amount' => $invoice->originalAmount,
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

        $event = $this->dispatcher->dispatch(new BeforeGatewayProcessingEvent($invoice, $gatewayEntity, $gateway, $paymentData), BeforeGatewayProcessingEvent::NAME);

        $paymentData = $event->getPaymentData();
        $gateway = $event->getGateway();
        $gatewayEntity = $event->getPaymentGateway();
        $invoice = $event->getInvoice();

        $paymentData['notifyUrl'] = url('/api/lk/handle/' . $gatewayEntity->adapter)->get();

        $response = $gateway->purchase($paymentData)->send();

        $this->dispatcher->dispatch(new AfterGatewayResponseEvent($invoice, $response), AfterGatewayResponseEvent::NAME);

        if ($response->isRedirect() && $response instanceof RedirectResponseInterface) {
            $response->redirect();
        } else {
            throw new PaymentException($response->getMessage() ?? ($response->getData()['message'] ?? 'Unknown error'));
        }
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
        $transactionId = $response->getTransactionId();

        $paidAmount = method_exists($response, 'getAmount') ? (float) $response->getAmount() : null;

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
}
