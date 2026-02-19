<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTimeImmutable;
use Flute\Core\Database\Entities\User;
use Cycle\ORM\Entity\Behavior;

#[Entity]
#[Behavior\CreatedAt(
    field: 'createdAt',
    column: 'created_at'
)]
class UserActionLog extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[BelongsTo(target: User::class, nullable: true)]
    public ?User $user = null;

    #[Column(type: "string")]
    public string $action;

    #[Column(type: "string", nullable: true)]
    public ?string $message = null;

    #[Column(type: "json", nullable: true)]
    public ?string $data = null;

    #[Column(type: "string", nullable: true)]
    public ?string $level = null;

    #[Column(type: "datetime")]
    public DateTimeImmutable $createdAt;

    public function getData(): ?array
    {
        return $this->data ? json_decode($this->data, true) : null;
    }

    public function setData(?array $data): void
    {
        $this->data = $data ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    }

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }
}
