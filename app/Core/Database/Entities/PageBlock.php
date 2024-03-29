<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\ManyToMany;

/**
 * @Entity()
 */
class PageBlock
{
    /** @Column(type="primary") */
    public $id;

    /** @BelongsTo(target="Page", nullable=false) */
    public $page;

    /** @Column(type="json") */
    public $json;

    /** @ManyToMany(target="Permission", though="PageBlockPermission") */
    public $permissions;
}