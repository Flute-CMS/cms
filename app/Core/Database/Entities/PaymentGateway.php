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
        new Index(columns: ["name"], unique: true),
        new Index(columns: ["adapter"], unique: true)
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
class PaymentGateway extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string", unique: true)]
    public string $name;

    #[Column(type: "string", nullable: true)]
    public ?string $image;

    #[Column(type: "string", unique: true)]
    public string $adapter;

    #[Column(type: "boolean", default: false)]
    public bool $enabled;

    #[Column(type: "json")]
    public string $additional;

    #[Column(type: "datetime")]
    public \DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    public function getSettings() : array
    {
        return json_decode($this->additional, true);
    }

    public function setSettings(array $settings) : void
    {
        $this->additional = json_encode($settings);
    }
}
