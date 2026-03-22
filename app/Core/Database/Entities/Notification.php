<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;

#[Entity]
#[Table(indexes: [
    new Index(columns: ["user_id", "viewed"]),
    new Index(columns: ["user_id", "created_at"]),
])]
#[Behavior\CreatedAt(
    field: 'createdAt',
    column: 'created_at'
)]
#[Behavior\UpdatedAt(
    field: 'updatedAt',
    column: 'updated_at'
)]
class Notification extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "text", nullable: true)]
    public ?string $icon = null;

    #[Column(type: "string", nullable: true)]
    public ?string $url = null;

    #[Column(type: "string")]
    public string $title;

    #[Column(type: "text")]
    public string $content;

    #[Column(type: "string")]
    public string $type;

    #[Column(type: "text", nullable: true)]
    public ?string $extra_data = null;

    #[Column(type: "boolean", default: false)]
    public bool $viewed = false;

    #[Column(type: "datetime")]
    public \DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    #[BelongsTo(target: "User", nullable: false)]
    public User $user;

    public function getExtraData(): ?array
    {
        return $this->extra_data ? json_decode($this->extra_data, true) : null;
    }

    public function setExtraData(?array $data): void
    {
        $this->extra_data = $data ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    }
}
