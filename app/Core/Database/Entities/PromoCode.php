<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;

#[Entity]
#[Table(
    indexes: [new Index(columns: ["code"], unique: true)]
)]
#[Behavior\CreatedAt(
    field: 'createdAt',
    column: 'created_at'
)]
#[Behavior\UpdatedAt(
    field: 'updatedAt',
    column: 'updated_at'
)]
class PromoCode extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $code;

    #[Column(type: "integer")]
    public int $max_usages;

    #[Column(type: "enum(amount, percentage)")]
    public string $type;

    #[Column(type: "float")]
    public float $value;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $expires_at = null;

    #[HasMany(target: "PromoCodeUsage")]
    public array $usages = [];

    #[Column(type: "datetime")]
    public \DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;
}
