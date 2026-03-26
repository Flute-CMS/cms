<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;

#[Entity]
#[Table(
    indexes: [
        new Index(columns: ["token"], unique: true),
        new Index(columns: ["user_id"]),
    ]
)]
class VerificationToken extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[BelongsTo(target: "User", nullable: false)]
    public User $user;

    #[Column(type: "string")]
    public string $token;

    #[Column(type: "datetime")]
    public \DateTimeImmutable $expiresAt;
}
