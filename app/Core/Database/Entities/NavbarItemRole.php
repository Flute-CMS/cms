<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

/**
 * @Entity()
 */
class NavbarItemRole
{
    /** @Column(type="primary") */
    public $id;

    /** @BelongsTo(target="NavbarItem", nullable=false) */
    public $navbarItem;

    /** @BelongsTo(target="Role", nullable=false) */
    public $role;
}
