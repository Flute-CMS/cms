<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity]
class Notification
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $icon;

    #[Column(type: "string", nullable: true)]
    public ?string $url = null;

    #[Column(type: "string")]
    public string $title;

    #[Column(type: "string")]
    public string $content;

    #[Column(type: "boolean", default: false)]
    public bool $viewed = false;

    #[Column(type: "timestamp")]
    public \DateTimeImmutable $created_at;

    #[BelongsTo(target: "User", nullable: false, cascade: true)]
    public User $user;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }
}
