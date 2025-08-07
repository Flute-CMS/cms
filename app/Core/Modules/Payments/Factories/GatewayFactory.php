<?php

namespace Flute\Core\Modules\Payments\Factories;

use Flute\Core\Database\Entities\PaymentGateway as PaymentGatewayEntity;
use Flute\Core\Modules\Payments\Exceptions\PaymentException;
use Omnipay\Omnipay;

class GatewayFactory
{
    /**
     * Creates a payment gateway instance.
     *
     * @param PaymentGatewayEntity $gatewayEntity Payment gateway entity.
     *
     * @return mixed Gateway instance.
     *
     * @throws PaymentException
     */
    public function create(PaymentGatewayEntity $gatewayEntity)
    {
        try {
            $gateway = Omnipay::create($gatewayEntity->adapter);
            $config = $this->decryptConfig($gatewayEntity->additional);
            $gateway->initialize($config);

            return $gateway;
        } catch (\Exception $e) {
            $masked = substr($gatewayEntity->additional, 0, 4) . '***';
            logs()->warning($e->getMessage(), ['adapter' => $gatewayEntity->adapter, 'config' => $masked]);

            if (is_debug()) {
                throw $e;
            }
        }
    }

    /**
     * Decrypts and decodes the gateway configuration.
     *
     * @param string $encryptedConfig Encrypted configuration string.
     *
     * @return array Decrypted configuration array.
     *
     * @throws PaymentException
     */
    private function decryptConfig(string $encryptedConfig): array
    {
        try {
            $json = encrypt()->decryptString($encryptedConfig);
        } catch (\Throwable $e) {
            $json = $encryptedConfig;
        }

        $config = \Nette\Utils\Json::decode($json, \Nette\Utils\Json::FORCE_ARRAY);

        if (!is_array($config)) {
            throw new PaymentException('Invalid gateway configuration');
        }

        if (isset($config['testMode'])) {
            $config['testMode'] = filter_var($config['testMode'], FILTER_VALIDATE_BOOLEAN) ?? false;
        }

        return $config;
    }
}
