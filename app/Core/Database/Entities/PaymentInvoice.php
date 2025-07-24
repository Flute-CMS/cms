<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\ORM\Entity\Behavior;
#[Entity]
#[Behavior\CreatedAt(
    field: 'createdAt',
    column: 'created_at'
)]
#[Behavior\UpdatedAt(
    field: 'updatedAt',
    column: 'updated_at'
)]
class PaymentInvoice extends ActiveRecord
{
    #[BelongsTo(target: "User")]
    public User $user;

    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $gateway;

    #[Column(type: "string")]
    public $transactionId;

    #[Column(type: "float")]
    public float $amount;

    #[Column(type: "float")]
    public float $originalAmount;

    #[BelongsTo(target: "PromoCode", nullable: true)]
    public ?PromoCode $promoCode;

    #[BelongsTo(target: "Currency", nullable: true)]
    public ?Currency $currency;

    #[Column(type: "boolean", default: false)]
    public bool $isPaid;

    #[Column(type: "json", nullable: true)]
    public ?string $additional = null;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $paidAt = null;

    #[Column(type: "datetime")]
    public \DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    public function getAdditional(): array
    {
        return json_decode($this->additional ?? '[]', true) ?? [];
    }

    public function setAdditional(array $additional): void
    {
        $this->additional = json_encode($additional);
    }
}
