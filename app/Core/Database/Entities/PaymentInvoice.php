<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

/**
 * @Entity()
 */
class PaymentInvoice
{
    /** 
     * @BelongsTo(target="User")
     */
    public $user;

    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string") */
    public $gateway;

    /** @Column(type="string") */
    public $transactionId;

    /** @Column(type="float") */
    public $amount;

    /** @Column(type="float") */
    public $originalAmount;

    /**
     * @BelongsTo(target="PromoCode", nullable=true)
     */
    public $promoCode;

    /**
     * @BelongsTo(target="Currency", nullable=true)
     */
    public $currency;

    /** @Column(type="boolean", default="false") */
    public $isPaid;

    /** @Column(type="datetime", nullable=true) */
    public $paidAt;

    /**
     * @Column(type="timestamp", default="CURRENT_TIMESTAMP")
     */
    public $created_at;

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }
}
