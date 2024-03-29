<?php

namespace Flute\Core\Payments;

use Flute\Core\Database\Entities\PaymentGateway as PaymentGatewayEntity;
use Omnipay\Common\Helper;
use Symfony\Component\EventDispatcher\EventDispatcher;

class GatewayInitializer
{
    private ?GatewayFactory $gatewayFactory;
    private ?PaymentProcessor $paymentProcessor;
    private ?PaymentPromo $promoValidator;
    private $initializedGateways = [];

    public function __construct(GatewayFactory $gatewayFactory, PaymentPromo $paymentPromo, EventDispatcher $eventDispatcher)
    {
        $this->gatewayFactory = $gatewayFactory;
        $this->promoValidator = $paymentPromo;
        $this->paymentProcessor = new PaymentProcessor($this->gatewayFactory, $eventDispatcher);

        $this->initializeGateways();
    }

    public function getAllGateways(): array
    {
        return $this->initializedGateways;
    }

    public function getGateway($name)
    {
        return $this->initializedGateways[$name] ?? null;
    }

    public function factory() : ?GatewayFactory
    {
        return $this->gatewayFactory;
    }

    public function processor() : ?PaymentProcessor
    {
        return $this->paymentProcessor;
    }

    public function promo() : ?PaymentPromo
    {
        return $this->promoValidator;
    }

    public function gatewayExists(string $gateway) : bool
    {
        return class_exists(Helper::getGatewayClassName($gateway));
    }

    protected function initializeGateways()
    {
        $gatewayEntities = rep(PaymentGatewayEntity::class)
            ->findAll(['enabled' => true]);

        foreach ($gatewayEntities as $gatewayEntity) {
            $gateway = $this->gatewayFactory->create($gatewayEntity);
            $this->initializedGateways[$gatewayEntity->name] = $gateway;
        }
    }
}
