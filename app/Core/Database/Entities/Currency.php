<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;

#[Entity]
#[Table(indexes: [new Index(columns: ["code"], unique: true)])]
#[Behavior\CreatedAt(
    field: 'createdAt',
    column: 'created_at'
)]
#[Behavior\UpdatedAt(
    field: 'updatedAt',
    column: 'updated_at'
)]
class Currency extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $code;

    #[Column(type: "float")]
    public float $minimum_value;

    #[Column(type: "float")]
    public float $exchange_rate;

    #[Column(type: "string", nullable: true)]
    public ?string $preset_amounts = null;

    #[Column(type: "boolean", default: false)]
    public bool $auto_rate = false;

    #[Column(type: "float", default: 0)]
    public float $rate_markup = 0;

    #[ManyToMany(target: "PaymentGateway", through: "CurrencyPaymentGateway")]
    public array $paymentGateways = [];

    #[Column(type: "datetime")]
    public \DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    public function addPayment(PaymentGateway $paymentGateway) : void
    {
        if (!in_array($paymentGateway, $this->paymentGateways, true)) {
            $this->paymentGateways[] = $paymentGateway;
        }
    }

    public function clearPayments() : void
    {
        $this->paymentGateways = [];
    }

    public function hasPayment(PaymentGateway $paymentGateway) : bool
    {
        return in_array($paymentGateway, $this->paymentGateways);
    }

    public function hasPaymentByKey(string $gateway) : bool
    {
        foreach ($this->paymentGateways as $paymentGateway) {
            if ($paymentGateway->name === $gateway) {
                return true;
            }
        }
        return false;
    }

    public function getPresetAmounts(): array
    {
        if (empty($this->preset_amounts)) {
            return [];
        }

        $decoded = json_decode($this->preset_amounts, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function setPresetAmounts(array $amounts): void
    {
        $amounts = array_values(array_filter(array_map('floatval', $amounts), static fn ($v) => $v > 0));
        sort($amounts);
        $this->preset_amounts = !empty($amounts) ? json_encode($amounts) : null;
    }

    public function removePayment(PaymentGateway $paymentGateway) : void
    {
        $this->paymentGateways = array_filter(
            $this->paymentGateways,
            fn($pg) => $pg !== $paymentGateway
        );
    }
}
