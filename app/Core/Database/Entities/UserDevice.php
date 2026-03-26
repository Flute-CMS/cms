<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;

#[Entity]
#[Table(
    indexes: [
        new Index(columns: ["ip"]),
        new Index(columns: ["user_id"])
    ]
)]
#[Behavior\CreatedAt(
    field: 'createdAt',
    column: 'created_at'
)]
#[Behavior\UpdatedAt(
    field: 'lastUsedAt',
    column: 'last_used_at'
)]
class UserDevice extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[BelongsTo(target: "User", nullable: false)]
    public User $user;

    #[HasMany(target: "RememberToken")]
    public array $rememberTokens = [];

    #[Column(type: "string")]
    public string $deviceDetails;

    #[Column(type: "string")]
    public string $ip;

    #[Column(type: "datetime", nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[Column(type: "datetime", nullable: true)]
    public ?DateTimeImmutable $lastUsedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->lastUsedAt = new DateTimeImmutable();
    }

    public function touch(): void
    {
        $this->lastUsedAt = new DateTimeImmutable();
    }
}
