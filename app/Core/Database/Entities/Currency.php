<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;

#[Entity]
#[Table(indexes: [new Index(columns: ["code"], unique: true)])]
class Currency
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $code;

    #[Column(type: "float")]
    public float $minimum_value;

    #[Column(type: "float")]
    public float $exchange_rate;

    #[ManyToMany(target: "PaymentGateway", through: "CurrencyPaymentGateway")]
    public array $paymentGateways = [];

    public function addPayment(PaymentGateway $paymentGateway): void
    {
        if (!in_array($paymentGateway, $this->paymentGateways, true)) {
            $this->paymentGateways[] = $paymentGateway;
        }
    }

    public function clearPayments(): void
    {
        $this->paymentGateways = [];
    }

    public function hasPayment(PaymentGateway $paymentGateway): bool
    {
        return in_array($paymentGateway, $this->paymentGateways, true);
    }

    public function hasPaymentByKey(string $gateway): bool
    {
        foreach ($this->paymentGateways as $paymentGateway) {
            if ($paymentGateway->name === $gateway) {
                return true;
            }
        }
        return false;
    }

    public function removePayment(PaymentGateway $paymentGateway): void
    {
        $this->paymentGateways = array_filter(
            $this->paymentGateways,
            fn($pg) => $pg !== $paymentGateway
        );
    }
}
