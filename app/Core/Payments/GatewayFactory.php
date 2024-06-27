<?php

namespace Flute\Core\Payments;

use Composer\Autoload\ClassLoader;
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

    private function freekassaFix()
    {
        /** @var ClassLoader */
        $loader = app()->getLoader();

        $loader->addClassMap([
            'Omnipay\\FreeKassa\\Message\\PurchaseRequest' => BASE_PATH . 'app/Core/Payments/Fixes/FreeKassa/PurchaseRequest.php',
            'Omnipay\\FreeKassa\\Message\\PurchaseResponse' => BASE_PATH . 'app/Core/Payments/Fixes/FreeKassa/PurchaseResponse.php',
        ]);

        $loader->register();
    }

    private function decryptConfig($encryptedConfig)
    {
        $config = \Nette\Utils\Json::decode($encryptedConfig, true);
        return $config;
    }
}
