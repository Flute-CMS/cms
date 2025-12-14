<?php

namespace Flute\Core\Modules\Payments\Events;

use Symfony\Contracts\EventDispatcher\Event;

class RegisterPaymentFactoriesEvent extends Event
{
    public const NAME = 'flute.payment.register';
}
