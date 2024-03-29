<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

/**
 * @Entity
 */
class PromoCodeUsage
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="datetime") */
    public $used_at;

    /**
     * @BelongsTo(target="User", cascade=true)
     */
    public $user;

    /**
     * @BelongsTo(target="PromoCode", cascade=true)
     */
    public $promoCode;

    /**
     * @BelongsTo(target="PaymentInvoice", cascade=true)
     */
    public $invoice;
}