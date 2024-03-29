<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\Annotated\Annotation\Table;

/**
 * @Entity()
 * @Table(
 *      indexes={
 *          @Index(columns={"name"}, unique=true),
 *          @Index(columns={"adapter"}, unique=true)
 *      }
 * )
 */
class PaymentGateway
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string", unique=true) */
    public $name;

    /** @Column(type="string", unique=true) */
    public $adapter;

    /** @Column(type="boolean", default=false) */
    public $enabled;

    /** @Column(type="text") */
    public $additional;
}
