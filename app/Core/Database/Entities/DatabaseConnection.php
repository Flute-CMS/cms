<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTime;

#[Entity]
class DatabaseConnection
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $mod;

    #[Column(type: "string")]
    public string $dbname;

    #[Column(type: "text", nullable: true)]
    public ?string $additional;

    #[BelongsTo(target: "Server", nullable: false, cascade: true)]
    public Server $server;

    #[Column(type: "timestamp", default: "CURRENT_TIMESTAMP")]
    public DateTime $created_at;

    public function __construct()
    {
        $this->created_at = new DateTime();
    }
}
