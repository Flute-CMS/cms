<?php

namespace Flute\Core\Modules\Payments\Events;

use Symfony\Contracts\EventDispatcher\Event;

class BeforeInvoiceCreatedEvent extends Event
{
    public const NAME = 'payment.before_invoice_created';

    protected $gatewayName;
    protected $amount;
    protected $promo;
    protected $currencyCode;
    protected $additionalData = [];

    public function __construct(string $gatewayName, $amount, ?string $promo = null, ?string $currencyCode = null, array $additionalData = [])
    {
        $this->gatewayName = $gatewayName;
        $this->amount = $amount;
        $this->promo = $promo;
        $this->currencyCode = $currencyCode;
        $this->additionalData = $additionalData;
    }

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getPromo(): ?string
    {
        return $this->promo;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    public function setAdditionalData(array $data): void
    {
        $this->additionalData = $data;
    }
}
