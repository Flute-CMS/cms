<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;

/**
 * @Entity()
 * 
 * @Table(
 *      indexes={
 *          @Index(columns={"key"}, unique=true)
 *      }
 * )
 */
class ThemeSettings
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string", unique=true) */
    public $key;

    /** @Column(type="string") */
    public $name;

    /** @Column(type="string") */
    public $value;

    /** @Column(type="string", nullable=true) */
    public $description;

    /**
     * @BelongsTo(target="Theme", nullable=false)
     */
    public $theme;
}
