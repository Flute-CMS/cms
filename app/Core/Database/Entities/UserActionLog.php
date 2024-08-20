<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity]
class UserActionLog
{
    #[Column(type: "primary")]
    public int $id;

    #[BelongsTo(target: "User", nullable: false)]
    public User $user;

    #[Column(type: "string")]
    public string $action;

    #[Column(type: "string", nullable: true)]
    public ?string $details = null;

    #[Column(type: "string", nullable: true)]
    public ?string $url = null;

    #[Column(type: "datetime")]
    public \DateTimeImmutable $actionDate;

    public function __construct()
    {
        $this->actionDate = new \DateTimeImmutable();
    }
}
