<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity]
class PromoCodeUsage extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "datetime")]
    public \DateTimeImmutable $used_at;

    #[BelongsTo(target: "User", cascade: true)]
    public User $user;

    #[BelongsTo(target: "PromoCode", cascade: true)]
    public PromoCode $promoCode;

    #[BelongsTo(target: "PaymentInvoice", cascade: true)]
    public PaymentInvoice $invoice;
}
