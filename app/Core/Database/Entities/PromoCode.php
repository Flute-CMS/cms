<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;

/**
 * @Entity
 */
class PromoCode
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string") */
    public $code;

    /** @Column(type="integer") */
    public $max_usages;

    /** @Column(type="enum(amount, percentage, subtract)") */
    public $type;

    /** @Column(type="float") */
    public $value;

    /** @Column(type="datetime") */
    public $expires_at;

    /** @HasMany(target="PromoCodeUsage") */
    public $usages;
}