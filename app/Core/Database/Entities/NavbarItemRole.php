<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;

#[Entity]
#[Table(
    indexes: [
        new Index(columns: ["navbarItem_id"]),
        new Index(columns: ["role_id"])
    ]
)]
class NavbarItemRole extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[BelongsTo(target: "NavbarItem", nullable: false)]
    public NavbarItem $navbarItem;

    #[BelongsTo(target: "Role", nullable: false)]
    public Role $role;
}
