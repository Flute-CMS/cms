<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity]
class ApiKeyPermission
{
    #[Column(type: "primary")]
    public int $id;

    #[BelongsTo(target: ApiKey::class)]
    public ApiKey $apiKey;

    #[BelongsTo(target: Permission::class)]
    public Permission $permission;
}
