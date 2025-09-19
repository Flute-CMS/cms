<?php

namespace Flute\Core\Modules\Payments\Initializers;

use Exception;
use Flute\Core\Database\Entities\PaymentGateway as PaymentGatewayEntity;
use Flute\Core\Modules\Payments\Events\RegisterPaymentFactoriesEvent;
use Flute\Core\Modules\Payments\Factories\GatewayFactory;
use Flute\Core\Modules\Payments\Processors\PaymentProcessor;
use Flute\Core\Modules\Payments\Services\PaymentPromo;
use Omnipay\Common\Helper;
use Symfony\Component\EventDispatcher\EventDispatcher;

class GatewayInitializer
{
    private ?GatewayFactory $gatewayFactory;

    private ?PaymentProcessor $paymentProcessor;

    private ?PaymentPromo $promoValidator;

    private array $initializedGateways = [];

    /**
     * Initializes the GatewayInitializer with necessary dependencies.
     *
     * @param GatewayFactory   $gatewayFactory  Factory to create payment gateways.
     * @param PaymentPromo     $paymentPromo    Service to handle promo codes.
     * @param EventDispatcher  $eventDispatcher Event dispatcher for handling events.
     */
    public function __construct(GatewayFactory $gatewayFactory, PaymentPromo $paymentPromo, EventDispatcher $eventDispatcher)
    {
        $this->gatewayFactory = $gatewayFactory;
        $this->promoValidator = $paymentPromo;
        $this->paymentProcessor = new PaymentProcessor($this->gatewayFactory, $eventDispatcher);

        $this->initializeGateways();
    }

    /**
     * Returns all initialized gateways.
     *
     * @return array List of initialized gateways.
     */
    public function getAllGateways(): array
    {
        return $this->initializedGateways;
    }

    /**
     * Retrieves a specific gateway by name.
     *
     * @param string $name Name of the gateway.
     *
     * @return mixed The gateway object or null if not found.
     */
    public function getGateway(string $name)
    {
        return $this->initializedGateways[$name] ?? null;
    }

    /**
     * Gets the gateway factory.
     *
     * @return GatewayFactory|null The gateway factory instance.
     */
    public function factory(): ?GatewayFactory
    {
        return $this->gatewayFactory;
    }

    /**
     * Gets the payment processor.
     *
     * @return PaymentProcessor|null The payment processor instance.
     */
    public function processor(): ?PaymentProcessor
    {
        return $this->paymentProcessor;
    }

    /**
     * Gets the promo code validator.
     *
     * @return PaymentPromo|null The promo validator instance.
     */
    public function promo(): ?PaymentPromo
    {
        return $this->promoValidator;
    }

    /**
     * Checks if a gateway exists.
     *
     * @param string $gateway Name of the gateway.
     *
     * @return bool True if the gateway exists, false otherwise.
     */
    public function gatewayExists(string $gateway): bool
    {
        return class_exists(Helper::getGatewayClassName($gateway));
    }

    /**
     * Initializes all enabled gateways.
     */
    protected function initializeGateways(): void
    {
        events()->dispatch(new RegisterPaymentFactoriesEvent(), RegisterPaymentFactoriesEvent::NAME);

        $gatewayEntities = is_performance() ? cache()->callback('payment_gateways_enabled', static fn () => PaymentGatewayEntity::findAll(['enabled' => true]), 3600) : PaymentGatewayEntity::findAll(['enabled' => true]);

        foreach ($gatewayEntities as $gatewayEntity) {
            try {
                $gateway = $this->gatewayFactory->create($gatewayEntity);
                if ($gateway !== null) {
                    $this->initializedGateways[$gatewayEntity->adapter] = $gateway;
                } else {
                    logs()->error("Failed to initialize gateway: {$gatewayEntity->adapter}");
                }
            } catch (Exception $e) {
                logs()->error("Failed to initialize gateway: {$gatewayEntity->adapter}");
            }
        }
    }
}
