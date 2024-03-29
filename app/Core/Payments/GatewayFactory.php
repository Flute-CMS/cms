<?php 

namespace Flute\Core\Payments;

use Omnipay\Omnipay;
use Flute\Core\Database\Entities\PaymentGateway as PaymentGatewayEntity;

class GatewayFactory
{
    public function create(PaymentGatewayEntity $gatewayEntity)
    {
        $gateway = Omnipay::getFactory()->create($gatewayEntity->adapter);
        $config = $this->decryptConfig($gatewayEntity->additional);
        $gateway->initialize($config);

        return $gateway;
    }

    private function decryptConfig($encryptedConfig)
    {
        $config = \Nette\Utils\Json::decode($encryptedConfig, true);
        return $config;
    }
}
