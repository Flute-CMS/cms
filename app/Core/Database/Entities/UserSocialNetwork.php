<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;

#[Entity]
#[Table(indexes: [
    new Index(columns: ["socialNetwork_id", "value"], unique: true)
])]
class UserSocialNetwork extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $value;

    #[Column(type: "string", nullable: true)]
    public ?string $url = null;

    #[Column(type: "string", nullable: true)]
    public ?string $name = null;

    #[Column(type: "boolean", default: false)]
    public bool $hidden = false;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $linkedAt;

    #[Column(type: "json", nullable: true)]
    public ?string $additional = null;

    #[BelongsTo(target: "SocialNetwork", nullable: false, cascade: true)]
    public SocialNetwork $socialNetwork;

    #[BelongsTo(target: "User", nullable: false, cascade: true)]
    public User $user;

    public function __construct()
    {
        $this->linkedAt = new \DateTimeImmutable();
    }

    public function getAdditional(): ?array
    {
        return $this->additional ? json_decode($this->additional, true) : null;
    }

    public function setAdditional(?array $additional): void
    {
        $this->additional = $additional ? json_encode($additional, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    }
}
