<?php

namespace Flute\Core\Modules\Payments\Events;

use Symfony\Contracts\EventDispatcher\Event;

class BeforePaymentEvent extends Event
{
    public const NAME = 'payment.before';

    protected $amount;

    protected $promo;

    public function __construct(int $amount, string $promo)
    {
        $this->amount = $amount;
        $this->promo = $promo;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getPromo()
    {
        return $this->promo;
    }
}
