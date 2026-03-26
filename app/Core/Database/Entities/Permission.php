<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;

#[Entity]
#[Table(indexes: [
    new Index(columns: ["name"], unique: true),
])]
#[Behavior\CreatedAt(
    field: 'createdAt',
    column: 'created_at'
)]
#[Behavior\UpdatedAt(
    field: 'updatedAt',
    column: 'updated_at'
)]
class Permission extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $name;

    #[Column(type: "string")]
    public string $desc;

    #[Column(type: "datetime")]
    public \DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;
}
