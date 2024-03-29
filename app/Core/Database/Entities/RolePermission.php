<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

/**
 * @Entity()
 */
class RolePermission
{
    /** @Column(type="primary") */
    public $id;

    /** @BelongsTo(target="Role", nullable=false) */
    public $role;

    /** @BelongsTo(target="Permission", nullable=false) */
    public $permission;
}