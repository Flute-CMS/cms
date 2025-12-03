<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;

#[Entity]
#[Table(
    indexes: [
        new Index(columns: ["enabled"])
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
class Server extends ActiveRecord
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

    #[Column(type: "string", default: "default")]
    public string $ranks = 'default';

    #[Column(type: "boolean", default: false)]
    public bool $ranks_premier = false;

    #[Column(type: "string", default: "webp")]
    public string $ranks_format = 'webp';

    #[Column(type: "text", nullable: true)]
    public ?string $additional = null;

    #[HasMany(target: "DatabaseConnection", cascade: true, nullable: true)]
    public array $dbconnections;

    #[Column(type: "datetime")]
    public \DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    #[Column(type: "boolean", default: true)]
    public bool $enabled = true;

    /**
     * Get database connection by mod
     */
    public function getDbConnection(string $mod): ?DatabaseConnection
    {
        foreach ($this->dbconnections as $dbConnection) {
            if ($dbConnection->mod === $mod) {
                return $dbConnection;
            }
        }
        return null;
    }

    /**
     * Get server connection string
     */
    public function getConnectionString(): string
    {
        return empty($this->display_ip) ? $this->ip . ':' . $this->port : $this->display_ip;
    }

    /**
     * Set settings for the server
     */
    public function setSettings(array $settings): void
    {
        $this->additional = json_encode($settings);
    }

    /**
     * Get settings for the server
     */
    public function getSettings(): array
    {
        return json_decode($this->additional ?? '{}', true) ?: [];
    }

    /**
     * Get settings for the server
     */
    public function getSetting(string $key): mixed
    {
        return $this->getSettings()[$key] ?? null;
    }

    public function getRank(?string $rank, ?int $points): string
    {
        $points = $points ?? 0;
        
        if ($this->ranks_premier) {
            $rankClass = 'gray-rank';
            
            if ($points > 30000) $rankClass = 'gold-rank';
            else if ($points > 25000) $rankClass = 'red-rank';
            else if ($points > 20000) $rankClass = 'pink-rank';
            else if ($points > 15000) $rankClass = 'purple-rank';
            else if ($points > 10000) $rankClass = 'blue-rank';
            else if ($points > 5000) $rankClass = 'wblue-rank';
            return '<div class="premier-rank ' . $rankClass . '">' . $points . '</div>';
        }

        if (empty($rank) || $rank === '0') {
            return '';
        }

        return '<img src="' . asset('assets/img/ranks/' . ($this->ranks ?? 'default') . '/' . $rank . '.' . ($this->ranks_format ?? 'webp')) . '" alt="' . $rank . '" loading="lazy">';
    }
}
