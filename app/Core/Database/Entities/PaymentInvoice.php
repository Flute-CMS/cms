<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity]
class PaymentInvoice
{
    #[BelongsTo(target: "User")]
    public User $user;

    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $gateway;

    #[Column(type: "string")]
    public string $transactionId;

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

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTime $paidAt = null;

    #[Column(type: "timestamp", default: "CURRENT_TIMESTAMP")]
    public \DateTimeImmutable $created_at;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }
}
