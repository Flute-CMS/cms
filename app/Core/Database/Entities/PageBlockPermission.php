<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

/**
 * @Entity()
 */
class PageBlockPermission
{
    /** @Column(type="primary") */
    public $id;

    /** @BelongsTo(target="Permission", nullable=false) */
    public $permission;
}
