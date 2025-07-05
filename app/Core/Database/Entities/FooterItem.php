<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;

#[Entity]
#[Table(
    indexes: [new Index(columns: ["position"])]
)]
#[Behavior\CreatedAt(
    field: 'createdAt',
    column: 'created_at'
)]
#[Behavior\UpdatedAt(
    field: 'updatedAt',
    column: 'updated_at'
)]
class FooterItem extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $title;

    #[Column(type: "string", nullable: true)]
    public ?string $icon;

    #[Column(type: "string", nullable: true)]
    public ?string $url;

    #[Column(type: "boolean", default: false)]
    public bool $new_tab;

    #[Column(type: "integer", default: 0)]
    public int $position = 0;

    #[BelongsTo(target: "FooterItem", nullable: true, innerKey: "parent_id")]
    public ?FooterItem $parent;

    #[HasMany(target: "FooterItem", nullable: true, outerKey: "parent_id")]
    public array $children = [];

    #[Column(type: "datetime")]
    public \DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    public function addChild(FooterItem $child) : void
    {
        if (!in_array($child, $this->children, true)) {
            $this->children[] = $child;
            $child->parent = $this;
        }
    }

    public function removeChild(FooterItem $child) : void
    {
        $this->children = array_filter(
            $this->children,
            fn($c) => $c !== $child
        );
        $child->parent = null;
    }
}
