<?php

namespace Flute\Core\Modules\Payments\Events;

use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Database\Entities\User;
use Symfony\Contracts\EventDispatcher\Event;

class PaymentSuccessEvent extends Event
{
    public const NAME = 'payment.success';

    protected $invoice;

    protected $user;

    public function __construct(PaymentInvoice $invoice, User $user)
    {
        $this->invoice = $invoice;
        $this->user = $user;
    }

    public function getInvoice(): PaymentInvoice
    {
        return $this->invoice;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
