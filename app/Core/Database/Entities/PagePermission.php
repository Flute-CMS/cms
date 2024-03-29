<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

/**
 * @Entity()
 */
class PagePermission
{
    /** @Column(type="primary") */
    public $id;

    /** @BelongsTo(target="Page", nullable=false) */
    public $page;

    /** @BelongsTo(target="Permission", nullable=false) */
    public $permission;
}