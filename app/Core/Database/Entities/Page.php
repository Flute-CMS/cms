<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;

#[Entity]
#[Table(
    indexes: [
        new Index(columns: ["route"], unique: true)
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
class Page extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $route;

    #[Column(type: "string")]
    public string $title;

    #[Column(type: "text", nullable: true)]
    public ?string $description = null;

    #[Column(type: "string", nullable: true)]
    public ?string $keywords = null;

    #[Column(type: "string", nullable: true)]
    public ?string $robots = null;

    #[Column(type: "string", nullable: true)]
    public ?string $og_image = null;

    #[HasMany(target: "PageBlock")]
    public array $blocks = [];

    #[ManyToMany(target: "Permission", through: "PagePermission")]
    public array $permissions = [];

    #[Column(type: "datetime")]
    public \DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    public function getId() : int
    {
        return $this->id;
    }

    public function getRoute() : string
    {
        return $this->route;
    }

    public function setRoute(string $route) : void
    {
        $this->route = $route;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function setTitle(string $title) : void
    {
        $this->title = $title;
    }

    public function getDescription() : ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description) : void
    {
        $this->description = $description;
    }

    public function getKeywords() : ?string
    {
        return $this->keywords;
    }

    public function setKeywords(?string $keywords) : void
    {
        $this->keywords = $keywords;
    }

    public function getRobots() : ?string
    {
        return $this->robots;
    }

    public function setRobots(?string $robots) : void
    {
        $this->robots = $robots;
    }

    public function getOgImage() : ?string
    {
        return $this->og_image;
    }

    public function setOgImage(?string $og_image) : void
    {
        $this->og_image = $og_image;
    }

    public function getBlocks()
    {
        return $this->blocks;
    }

    public function getPermissions()
    {
        return $this->permissions;
    }

    public function addBlock(PageBlock $block) : void
    {
        if (!in_array($block, $this->blocks, true)) {
            $this->blocks[] = $block;
        }
    }

    public function removeBlock(PageBlock $block) : void
    {
        $this->blocks = array_filter(
            $this->blocks,
            fn($b) => $b !== $block
        );
    }

    public function removeAllBlocks() : void
    {
        $this->blocks = [];
    }

    public function addPermission(Permission $permission) : void
    {
        if (!in_array($permission, $this->permissions, true)) {
            $this->permissions[] = $permission;
        }
    }

    public function hasPermission(Permission $permission) : bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function removePermission(Permission $permission) : void
    {
        $this->permissions = array_filter(
            $this->permissions,
            fn($p) => $p !== $permission
        );
    }
}
