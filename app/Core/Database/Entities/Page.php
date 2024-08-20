<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;

#[Entity(repository: "Flute\Core\Database\Repositories\PageRepository")]
#[Table(
    indexes: [
        new Index(columns: ["route"], unique: true)
    ]
)]
class Page
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
    public ?string $og_title = null;

    #[Column(type: "text", nullable: true)]
    public ?string $og_description = null;

    #[Column(type: "string", nullable: true)]
    public ?string $og_image = null;

    #[HasMany(target: "PageBlock")]
    public array $blocks = [];

    #[ManyToMany(target: "Permission", through: "PagePermission")]
    public array $permissions = [];

    public function addBlock(PageBlock $block): void
    {
        if (!in_array($block, $this->blocks, true)) {
            $this->blocks[] = $block;
        }
    }

    public function removeBlock(PageBlock $block): void
    {
        $this->blocks = array_filter(
            $this->blocks,
            fn($b) => $b !== $block
        );
    }

    public function removeAllBlocks(): void
    {
        $this->blocks = [];
    }

    public function addPermission(Permission $permission): void
    {
        if (!in_array($permission, $this->permissions, true)) {
            $this->permissions[] = $permission;
        }
    }

    public function hasPermission(Permission $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function removePermission(Permission $permission): void
    {
        $this->permissions = array_filter(
            $this->permissions,
            fn($p) => $p !== $permission
        );
    }
}
