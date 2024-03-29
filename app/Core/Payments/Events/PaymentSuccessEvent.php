<?php

namespace Flute\Core\Payments\Events;

use Flute\Core\Database\Entities\PaymentInvoice;
use Symfony\Contracts\EventDispatcher\Event;

class PaymentSuccessEvent extends Event
{
    public const NAME = 'payment.success';

    protected $invoice;

    public function __construct(PaymentInvoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function getInvoice(): PaymentInvoice
    {
        return $this->invoice;
    }
}
