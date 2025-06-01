<?php

namespace Flute\Core\Modules\Payments\Events;

use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Database\Entities\PaymentGateway;
use Omnipay\Common\GatewayInterface;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeGatewayProcessingEvent extends Event
{
    public const NAME = 'gateway.before_processing';

    protected $invoice;
    protected $gateway;
    protected $paymentGateway;
    protected $paymentData;

    public function __construct(PaymentInvoice $invoice, PaymentGateway $paymentGateway, ?GatewayInterface $gateway, array &$paymentData)
    {
        $this->invoice = $invoice;
        $this->paymentGateway = $paymentGateway;
        $this->gateway = $gateway;
        $this->paymentData = $paymentData;
    }

    public function getInvoice(): PaymentInvoice
    {
        return $this->invoice;
    }

    public function getPaymentGateway(): PaymentGateway
    {
        return $this->paymentGateway;
    }

    public function getGateway(): GatewayInterface
    {
        return $this->gateway;
    }

    public function getPaymentdata(): array
    {
        return $this->paymentData;
    }

    public function setPaymentData(array $data)
    {
        $this->paymentData = $data;
    }
}
