<?php

namespace Flute\Core\Modules\Payments\Factories;

use Flute\Core\Database\Entities\PaymentGateway as PaymentGatewayEntity;
use Flute\Core\Modules\Payments\Exceptions\PaymentException;
use Omnipay\Omnipay;
use Throwable;

class GatewayFactory
{
    /**
     * Creates a payment gateway instance.
     *
     * @param PaymentGatewayEntity $gatewayEntity Payment gateway entity.
     *
     * @throws PaymentException
     * @return mixed Gateway instance.
     */
    public function create(PaymentGatewayEntity $gatewayEntity)
    {
        try {
            $gateway = Omnipay::create($gatewayEntity->adapter);
            $config = $this->decryptConfig($gatewayEntity);
            $gateway->initialize($config);

            return $gateway;
        } catch (Throwable $e) {
            $masked = substr($gatewayEntity->additional, 0, 4) . '***';
            logs()->warning("Gateway init failed: {$gatewayEntity->adapter}: {$e->getMessage()}", [
                'adapter' => $gatewayEntity->adapter,
                'config' => $masked,
            ]);

            if (is_debug()) {
                throw new PaymentException("Failed to initialize gateway '{$gatewayEntity->adapter}'", 0, $e);
            }

            return null;
        }
    }

    /**
     * Decrypts and decodes the gateway configuration.
     * If the config is plaintext JSON, it will be auto-encrypted and saved.
     */
    private function decryptConfig(PaymentGatewayEntity $gatewayEntity): array
    {
        $raw = $gatewayEntity->additional;

        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            try {
                $gatewayEntity->additional = encrypt()->encryptString($raw);
                $gatewayEntity->save();
            } catch (Throwable) {
            }

            return $this->normalizeConfig($decoded);
        }

        try {
            $json = encrypt()->decryptString($raw);
        } catch (Throwable $e) {
            logs()->warning('Gateway config decryption failed: ' . $e->getMessage());

            throw new PaymentException('Failed to decrypt gateway configuration');
        }

        $config = \Nette\Utils\Json::decode($json, \Nette\Utils\Json::FORCE_ARRAY);

        if (!is_array($config)) {
            throw new PaymentException('Invalid gateway configuration');
        }

        return $this->normalizeConfig($config);
    }

    private function normalizeConfig(array $config): array
    {
        if (isset($config['keys']) && is_array($config['keys'])) {
            $keys = $config['keys'];
            unset($config['keys']);
            $config = array_merge($config, $keys);
        }

        if (isset($config['testMode'])) {
            $config['testMode'] = filter_var($config['testMode'], FILTER_VALIDATE_BOOLEAN) ?? false;
        }

        return $config;
    }
}
