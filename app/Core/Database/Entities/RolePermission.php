<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;

#[Entity]
#[Table(indexes: [
    new Index(columns: ["role_id", "permission_id"], unique: true),
])]
class RolePermission extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[BelongsTo(target: "Role", nullable: false)]
    public Role $role;

    #[BelongsTo(target: "Permission", nullable: false)]
    public Permission $permission;
}
