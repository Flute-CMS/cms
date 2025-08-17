<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\Annotated\Annotation\Table;
use Cycle\ORM\Entity\Behavior;

#[Entity]
#[Table(
    indexes: [
        new Index(columns: ["key"], unique: true)
    ]
)]
#[Behavior\CreatedAt(
    field: 'createdAt',
    column: 'created_at'
)]
#[Behavior\UpdatedAt(
    field: 'updatedAt',
    column: 'updated_at'
)]
class SocialNetwork extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $key;

    #[Column(type: "text")]
    public string $settings;

    #[Column(type: "integer", default: 0)]
    public int $cooldownTime = 0;

    #[Column(type: "boolean", default: true)]
    public bool $allowToRegister;

    #[Column(type: "text")]
    public string $icon; // svg or png or icon

    #[Column(type: "boolean", default: false)]
    public bool $enabled;

    #[Column(type: "datetime")]
    public \DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    public function getSettings() : array
    {
        return empty($this->settings) ? [] : (json_decode($this->settings, true)['keys'] ?? []);
    }
}