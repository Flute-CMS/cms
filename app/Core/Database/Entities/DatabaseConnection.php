<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;
use DateTimeImmutable;
use Cycle\ORM\Entity\Behavior;

#[Entity]
#[Table(
    indexes: [new Index(columns: ["mod", "dbname"])]
)]
#[Behavior\CreatedAt(
    field: 'createdAt',
    column: 'created_at'
)]
#[Behavior\UpdatedAt(
    field: 'updatedAt',
    column: 'updated_at'
)]
class DatabaseConnection extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $mod;

    #[Column(type: "string")]
    public string $dbname;

    #[Column(type: "text", nullable: true)]
    public ?string $additional;

    #[BelongsTo(target: Server::class, nullable: false, cascade: true)]
    public Server $server;

    #[Column(type: "datetime")]
    public DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;

    public function getAdditional() : array
    {
        return json_decode($this->additional ?? '{}', true);
    }
}
