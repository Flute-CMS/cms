<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity]
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
    public $additional = null;

    #[BelongsTo(target: "SocialNetwork", nullable: false, cascade: true)]
    public SocialNetwork $socialNetwork;

    #[BelongsTo(target: "User", nullable: false, cascade: true)]
    public User $user;

    public function __construct()
    {
        $this->linkedAt = new \DateTimeImmutable();
    }
}
