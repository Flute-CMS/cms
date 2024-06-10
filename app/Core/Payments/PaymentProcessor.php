<?php

namespace Flute\Core\Payments;

use Flute\Core\Database\Entities\Currency;
use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Database\Entities\PromoCodeUsage;
use Flute\Core\Payments\Events\AfterGatewayResponseEvent;
use Flute\Core\Payments\Events\AfterPaymentCreatedEvent;
use Flute\Core\Payments\Events\BeforeGatewayProcessingEvent;
use Flute\Core\Payments\Events\BeforePaymentEvent;
use Flute\Core\Payments\Events\PaymentFailedEvent;
use Flute\Core\Payments\Events\PaymentSuccessEvent;
use Flute\Core\Payments\Exceptions\PaymentException;
use Flute\Core\Payments\GatewayFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;

class PaymentProcessor
{
    protected $gatewayFactory;
    protected $dispatcher;

    public function __construct(GatewayFactory $gatewayFactory, EventDispatcher $eventDispatcher)
    {
        $this->gatewayFactory = $gatewayFactory;
        $this->dispatcher = $eventDispatcher;
    }

    /**
     * Создание счета и переадресация на оплату
     */
    public function purchase(string $gatewayName, int $amount, ?string $promo = null, ?string $currencyCode = null)
    {
        $gateway = rep(PaymentGateway::class)->findOne([
            'name' => $gatewayName,
        ]);

        if (!$gateway) {
            throw new PaymentException("Gateway {$gatewayName} wasn't found");
        }

        if ($gateway->enabled == false) {
            throw new PaymentException("Gateway {$gatewayName} is disabled");
        }

        $this->dispatcher->dispatch(new BeforePaymentEvent($amount, $promo), BeforePaymentEvent::NAME);

        $newAmount = $amount;

        if ($promo) {
            $user = user()->getCurrentUser();
            try {
                $promoData = payments()->promo()->validate($promo, $user->id);
                $newAmount = $this->calculatePromoBonus($promoData, $amount);
            } catch (\Exception $e) {
                // Обработка исключений, связанных с промокодом
                throw new PaymentException($e->getMessage());
            }
        }

        $invoice = new PaymentInvoice;

        if (!empty($currencyCode)) {
            $currency = $this->getCurrency($gateway, $invoice, $currencyCode);
            if ($currency) {
                $amount = $this->convertAmountToCurrency($amount, $currency);
            }
        }

        $invoice->originalAmount = $amount;
        $invoice->amount = $newAmount;

        $invoice->promoCode = payments()->promo()->get($promo);
        $invoice->gateway = $gateway->name;
        $invoice->transactionId = uniqid();
        $invoice->isPaid = false;
        $invoice->user = user()->getCurrentUser();

        $this->dispatcher->dispatch(new AfterPaymentCreatedEvent($invoice), AfterPaymentCreatedEvent::NAME);

        transaction($invoice)->run();

        return $this->processPayment($invoice, $gateway);
    }

    protected function convertAmountToCurrency($amount, Currency $currency)
    {
        return $amount * $currency->exchange_rate;
    }

    protected function getCurrency(PaymentGateway $gateway, PaymentInvoice $paymentInvoice, string $code)
    {
        $currency = rep(Currency::class)->findOne([
            'code' => $code
        ]);

        if (empty($currency))
            return;

        if (!$currency->hasPayment($gateway))
            return;

        $paymentInvoice->currency = $currency;

        return $currency;
    }

    private function applyPromoCode($amount, $promoData)
    {
        switch ($promoData['type']) {
            case 'amount':
                return max(0, $amount - $promoData['value']);
            case 'percentage':
                return max(0, $amount - ($amount * $promoData['value'] / 100));
            // case 'subtract':
            //     return $amount - $promoData['value'];
        }
        return $amount;
    }

    public function handlePayment(string $gatewayName)
    {
        if (!payments()->gatewayExists($gatewayName))
            throw new \Exception('Gateway is not exists');

        $gatewayEntity = rep(PaymentGateway::class)
            ->findOne(['enabled' => true, 'adapter' => $gatewayName]);

        if (!$gatewayEntity)
            throw new \Exception("Gateway wasn't found");

        $gatewayFactory = $this->gatewayFactory->create($gatewayEntity);

        $response = $gatewayFactory->completePurchase(request()->input())->send();

        if ($response->isSuccessful()) {
            /** 
             * @var PaymentInvoice
             */
            $invoice = rep(PaymentInvoice::class)->select()->where([
                'transactionId' => $response->getTransactionId()
            ])->load(['user', 'promoCode'])->fetchOne();

            if (!$invoice)
                throw new \Exception("Invoice wasn't found");

            if ($invoice->isPaid == true)
                throw new \Exception("Invoice is paid");

            $sum = $invoice->amount;
            $user = user()->get($invoice->user->id);

            $this->dispatcher->dispatch(new PaymentSuccessEvent($invoice, $user), PaymentSuccessEvent::NAME);

            $this->markInvoiceAsPaid($invoice);

            if ($invoice->promoCode) {
                $amount = $this->calculateTotalAmount($invoice, $invoice->promoCode, $user);

                $sum = $amount;
            }

            user()->topup($sum, $user);

            return true;
        } else {
            $this->dispatcher->dispatch(new PaymentFailedEvent($response), PaymentFailedEvent::NAME);

            throw new PaymentException($response->getMessage());
        }
    }

    public function markInvoiceAsPaid(PaymentInvoice $invoice)
    {
        $invoice->isPaid = true;
        $invoice->paidAt = new \DateTime;
        transaction($invoice)->run();
    }

    public function calculateTotalAmount($invoice, $promo, $user)
    {
        $totalAmount = $invoice->amount;

        if ($promo) {
            try {
                $promoData = payments()->promo()->validate($promo->code, $user->id);
                $totalAmount = $this->calculatePromoBonus($promoData, $invoice->amount);

                $this->recordPromoUsage($promo, $user, $invoice);
            } catch (\Exception $e) {
                // Обработка исключений, связанных с промокодом
            }
        }

        return $totalAmount;
    }

    public function calculatePromoBonus($promoData, $amount)
    {
        switch ($promoData['type']) {
            // case 'subtract':
            //     return $originalAmount;

            case 'percentage':
                return $amount * (1 + ($promoData['value'] / 100));

            case 'amount':
                return $amount + $promoData['value'];
        }
        return $amount;
    }

    public function recordPromoUsage($promo, $user, $invoice)
    {
        $usage = new PromoCodeUsage;
        $usage->promoCode = $promo;
        $usage->invoice = $invoice;
        $usage->user = $user;
        $usage->used_at = new \DateTime;
        transaction($usage)->run();
    }

    /**
     * Инициализация провайдера для покупки
     */
    public function processPayment(PaymentInvoice $invoice, PaymentGateway $gatewayEntity)
    {
        $gateway = $this->gatewayFactory->create($gatewayEntity);

        $additional = \Nette\Utils\Json::decode($gatewayEntity->additional);

        foreach ($additional as $key => $val) {
            $additional->$key = str_replace(["{{amount}}", "{{transactionId}}", "{{currency}}"], [$invoice->originalAmount, $invoice->transactionId, $invoice->currency->code], $val);
        }

        $paymentData = array_merge([
            'amount' => $invoice->originalAmount,
            'transactionId' => $invoice->transactionId,
            'cancelUrl' => url('/lk/fail')->get(),
            'returnUrl' => url('/lk/success')->get(),
        ], (array) $additional);

        if (!isset($paymentData['currency']))
            $paymentData['currency'] = $invoice->currency->code;

        $event = $this->dispatcher->dispatch(new BeforeGatewayProcessingEvent($invoice, $gatewayEntity, $gateway, $paymentData), BeforeGatewayProcessingEvent::NAME);

        $paymentData = $event->getPaymentdata();
        $gateway = $event->getGateway();
        $gatewayEntity = $event->getPaymentGateway();
        $invoice = $event->getInvoice();

        $paymentData['notifyUrl'] = url('/api/lk/handle/' . $gatewayEntity->adapter)->get();

        $response = $gateway->purchase($paymentData)->send();

        $this->dispatcher->dispatch(new AfterGatewayResponseEvent($invoice, $response), AfterGatewayResponseEvent::NAME);

        if ($response->isRedirect()) {
            return $response->redirect();
        } else {
            throw new PaymentException($response->getMessage() ?? $response->getData()->message);
        }
    }
}