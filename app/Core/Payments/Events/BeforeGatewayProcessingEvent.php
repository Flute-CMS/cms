<?php

namespace Flute\Core\Payments\Events;

use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Database\Entities\PaymentGateway;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeGatewayProcessingEvent extends Event
{
    public const NAME = 'gateway.before_processing';

    protected $invoice;
    protected $gateway;

    public function __construct(PaymentInvoice $invoice, PaymentGateway $gateway)
    {
        $this->invoice = $invoice;
        $this->gateway = $gateway;
    }

    public function getInvoice(): PaymentInvoice
    {
        return $this->invoice;
    }

    public function getGateway(): PaymentGateway
    {
        return $this->gateway;
    }
}
