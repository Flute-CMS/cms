<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity]
class CurrencyPaymentGateway extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[BelongsTo(target: "Currency", nullable: false)]
    public Currency $currency;

    #[BelongsTo(target: "PaymentGateway", nullable: false)]
    public PaymentGateway $paymentGateway;
}
