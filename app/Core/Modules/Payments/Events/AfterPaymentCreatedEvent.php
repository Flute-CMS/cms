<?php

namespace Flute\Core\Modules\Payments\Events;

use Flute\Core\Database\Entities\PaymentInvoice;
use Symfony\Contracts\EventDispatcher\Event;

class AfterPaymentCreatedEvent extends Event
{
    public const NAME = 'payment.after_created';

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
