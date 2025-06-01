<?php

namespace Flute\Core\Modules\Payments\Events;

use Flute\Core\Database\Entities\PaymentInvoice;
use Omnipay\Common\Message\ResponseInterface;
use Symfony\Contracts\EventDispatcher\Event;

class AfterGatewayResponseEvent extends Event
{
    public const NAME = 'gateway.after_response';

    protected $invoice;
    protected $response;

    public function __construct(PaymentInvoice $invoice, ResponseInterface $response)
    {
        $this->invoice = $invoice;
        $this->response = $response;
    }

    public function getInvoice(): PaymentInvoice
    {
        return $this->invoice;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
