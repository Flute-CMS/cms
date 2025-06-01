<?php

namespace Flute\Core\Modules\Payments\Events;

use Symfony\Contracts\EventDispatcher\Event;
use Flute\Core\Database\Entities\PaymentInvoice;

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
