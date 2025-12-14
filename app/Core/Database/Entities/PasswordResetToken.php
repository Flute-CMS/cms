<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity]
class PasswordResetToken extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[BelongsTo(target: "User", nullable: false, cascade: true)]
    public User $user;

    #[Column(type: "string")]
    public string $token;

    #[Column(type: "datetime")]
    public \DateTimeImmutable $expiry;
}
