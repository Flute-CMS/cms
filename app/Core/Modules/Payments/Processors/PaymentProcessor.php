<?php

namespace Flute\Core\Modules\Payments\Processors;

use Flute\Core\Database\Entities\Currency;
use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Database\Entities\PromoCode;
use Flute\Core\Database\Entities\PromoCodeUsage;
use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Payments\Events\AfterGatewayResponseEvent;
use Flute\Core\Modules\Payments\Events\AfterPaymentCreatedEvent;
use Flute\Core\Modules\Payments\Events\BeforeGatewayProcessingEvent;
use Flute\Core\Modules\Payments\Events\BeforePaymentEvent;
use Flute\Core\Modules\Payments\Events\PaymentFailedEvent;
use Flute\Core\Modules\Payments\Events\PaymentSuccessEvent;
use Flute\Core\Modules\Payments\Exceptions\PaymentException;
use Flute\Core\Modules\Payments\Factories\GatewayFactory;
use Nette\Utils\Random;
use Omnipay\Common\Message\ResponseInterface;
use Omnipay\Common\Message\RedirectResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
     * @return PaymentInvoice The created invoice.
     *
     * @throws PaymentException
     */
    public function createInvoice(string $gatewayName, $amount, ?string $promo = null, ?string $currencyCode = null) : PaymentInvoice
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

        $invoiceAmount = $amount;
        $user = user()->getCurrentUser();

        $invoice = new PaymentInvoice;

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

        return $invoice;
    }

    /**
     * Generates a unique transaction ID.
     *
     * @return int Unique transaction ID.
     */
    protected function generateTransactionId() : int
    {
        return (int) Random::generate(12, '0-9');
    }

    /**
     * Converts the amount to the specified currency.
     *
     * @param float    $amount   Amount to convert.
     * @param Currency $currency Currency entity.
     *
     * @return float Converted amount.
     */
    protected function convertAmountToCurrency($amount, Currency $currency) : float
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
    protected function getCurrency(PaymentGateway $gateway, PaymentInvoice $paymentInvoice, string $code) : ?Currency
    {
        $currency = Currency::findOne(['code' => $code]);

        if (empty($currency) || !$currency->hasPayment($gateway)) {
            return null;
        }

        $paymentInvoice->currency = $currency;

        return $currency;
    }

    /**
     * Handles the payment response from the gateway.
     *
     * @param string $gatewayName Name of the gateway.
     *
     * @return void
     *
     * @throws PaymentException
     */
    public function handlePayment(string $gatewayName) : void
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
     * Completes the payment process with the gateway.
     *
     * @param mixed $gateway Gateway instance.
     * @param array $input   Input data from the request.
     *
     * @return mixed Response from the gateway.
     *
     * @throws PaymentException
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
        } else {
            throw new PaymentException('Unsupported gateway');
        }
    }

    /**
     * Processes a successful payment.
     *
     * @param mixed $response Response from the gateway.
     *
     * @return void
     *
     * @throws PaymentException
     */
    protected function processSuccessfulPayment($response) : void
    {
        $transactionId = $response->getTransactionId();

        $this->setInvoiceAsPaid($transactionId);
    }

    /**
     * Sets the invoice as paid.
     *
     * @param string $transactionId Transaction ID.
     *
     * @throws PaymentException
     *
     * @return void
     */
    public function setInvoiceAsPaid(string $transactionId) : void
    {
        $invoice = PaymentInvoice::query()->forUpdate()->where(['transactionId' => $transactionId])->fetchOne();

        if (!$invoice) {
            throw new PaymentException("Invoice wasn't found");
        }

        if ($invoice->isPaid) {
            throw new PaymentException("Invoice is already paid");
        }

        $user = user()->get($invoice->user->id);

        $promo = $invoice->promoCode;
        $amount = $invoice->originalAmount;

        $promoBonus = 0;

        if ($promo) {
            $promoData = payments()->promo()->validate($promo->code, $user->id, $amount);
            $promoBonus = $this->calculatePromoBonus($promoData, $amount);
        }

        $this->dispatcher->dispatch(new PaymentSuccessEvent($invoice, $user), PaymentSuccessEvent::NAME);
        $invoice->isPaid = true;
        $invoice->paidAt = new \DateTimeImmutable();
        transaction($invoice)->run();

        $totalAmount = $invoice->amount + $promoBonus;

        if ($promo) {
            $this->recordPromoUsage($promo, $user, $invoice);
        }

        user()->topup($totalAmount, $user);
    }

    /**
     * Processes a failed payment.
     *
     * @param ResponseInterface $response Response from the gateway.
     *
     * @return void
     *
     * @throws PaymentException
     */
    protected function processFailedPayment(ResponseInterface $response) : void
    {
        $this->dispatcher->dispatch(new PaymentFailedEvent($response), PaymentFailedEvent::NAME);
        throw new PaymentException($response->getMessage());
    }

    /**
     * Marks the invoice as paid.
     *
     * @param PaymentInvoice $invoice Invoice to mark as paid.
     *
     * @return void
     */
    public function markInvoiceAsPaid(PaymentInvoice $invoice) : void
    {
        $invoice->isPaid = true;
        $invoice->paidAt = new \DateTimeImmutable();
        transaction($invoice)->run();
    }

    /**
     * Calculates the promo bonus based on promo data.
     *
     * @param array $promoData Promo data array.
     * @param float $amount    Original amount.
     *
     * @return float Calculated promo bonus.
     */
    public function calculatePromoBonus(array $promoData, $amount) : float
    {
        switch ($promoData['type']) {
            case 'percentage':
                return $amount * ($promoData['value'] / 100);

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
     *
     * @return void
     */
    public function recordPromoUsage(PromoCode $promo, User $user, PaymentInvoice $invoice) : void
    {
        $usage = new PromoCodeUsage();
        $usage->promoCode = $promo;
        $usage->invoice = $invoice;
        $usage->user = $user;
        $usage->used_at = new \DateTimeImmutable();
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
}
