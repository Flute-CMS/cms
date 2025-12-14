<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity]
class UserBlock extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[BelongsTo(target: "User", nullable: false)]
    public User $user;

    #[BelongsTo(target: "User", nullable: false)]
    public User $blockedBy;

    #[Column(type: "string")]
    public string $reason;

    #[Column(type: "datetime")]
    public \DateTimeImmutable $blockedFrom;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $blockedUntil = null;

    #[Column(type: "boolean", default: true)]
    public bool $isActive = true;

    public function __construct()
    {
        $this->blockedFrom = new \DateTimeImmutable();
    }
}
