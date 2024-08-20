<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\ORM\Collection\Pivoted\PivotedCollection;

#[Entity]
class Server
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $name;

    #[Column(type: "string")]
    public string $ip;

    #[Column(type: "int")]
    public int $port;

    #[Column(type: "string")]
    public string $mod;

    #[Column(type: "string", nullable: true)]
    public ?string $rcon = null;

    #[Column(type: "string", nullable: true)]
    public ?string $display_ip = null;

    #[HasMany(target: "DatabaseConnection", cascade: true, nullable: true)]
    public array $dbconnections;

    #[Column(type: "timestamp", default: "CURRENT_TIMESTAMP")]
    public \DateTimeImmutable $created_at;

    #[Column(type: "boolean", default: true)]
    public bool $enabled = true;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getDbConnection(string $mod): ?DatabaseConnection
    {
        foreach ($this->dbconnections as $dbConnection) {
            if ($dbConnection->mod === $mod) {
                return $dbConnection;
            }
        }
        return null;
    }
}
