<?php

namespace Flute\Core\Payments\Events;

use Omnipay\Common\Message\ResponseInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PaymentFailedEvent extends Event
{
    public const NAME = 'payment.failed';

    protected $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
