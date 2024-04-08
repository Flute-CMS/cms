<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

/**
 * @Entity()
 */
class CurrencyPaymentGateway
{
    /** @Column(type="primary") */
    public $id;

    /** @BelongsTo(target="Currency", nullable=false) */
    public $currency;

    /** @BelongsTo(target="PaymentGateway", nullable=false) */
    public $paymentGateway;
}