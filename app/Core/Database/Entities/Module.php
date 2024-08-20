<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;

#[Entity]
#[Table(
    indexes: [
        new Index(columns: ["key"], unique: true)
    ]
)]
class Module
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $key;

    #[Column(type: "string")]
    public string $name;

    #[Column(type: "string", nullable: true)]
    public ?string $description = null;

    #[Column(type: "string", nullable: true)]
    public ?string $installedVersion = null;

    #[Column(type: "enum(active,disabled,notinstalled)", default: "notinstalled")]
    public string $status;

    #[Column(type: "timestamp", default: "CURRENT_TIMESTAMP")]
    public \DateTimeImmutable $created_at;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }
}
