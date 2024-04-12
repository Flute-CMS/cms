<?php

namespace Flute\Core\Payments;

use Omnipay\FreeKassa\Message\PurchaseRequest;
use Omnipay\Omnipay;
use Flute\Core\Database\Entities\PaymentGateway as PaymentGatewayEntity;

class GatewayFactory
{
    public function create(PaymentGatewayEntity $gatewayEntity)
    {
        try {
            $this->freekassaFix();

            $gateway = Omnipay::getFactory()->create($gatewayEntity->adapter);
            $config = $this->decryptConfig($gatewayEntity->additional);
            $gateway->initialize($config);

            return $gateway;
        } catch (\RuntimeException $e) {
            logs()->warning($e);
        }
    }

    // #TODO: Replace it later
    private function freekassaFix()
    {
        app()->getLoader()->setPsr4('Omnipay\\FreeKassa\\Message\\', BASE_PATH . 'app/Core/Payments/Fixes/FreeKassa/');
        app()->getLoader()->register();
    }

    private function decryptConfig($encryptedConfig)
    {
        $config = \Nette\Utils\Json::decode($encryptedConfig, true);
        return $config;
    }
}
