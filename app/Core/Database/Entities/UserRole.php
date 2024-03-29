<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

/**
 * @Entity()
 */
class UserRole
{
    /** @Column(type="primary") */
    public $id;

    /** @BelongsTo(target="User", nullable=false) */
    public $user;

    /** @BelongsTo(target="Role", nullable=false) */
    public $role;
}