<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Cycle\ORM\Collection\Pivoted\PivotedCollection;

#[Entity]
class PageBlock
{
    #[Column(type: "primary")]
    public int $id;

    #[BelongsTo(target: "Page", nullable: false)]
    public Page $page;

    #[Column(type: "json")]
    public string $json;

    #[ManyToMany(target: "Permission", through: "PageBlockPermission")]
    public array $permissions;
}