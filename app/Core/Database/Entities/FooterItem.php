<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;

#[Entity]
class FooterItem
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $title;

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

    public function addChild(FooterItem $child): void
    {
        if (!in_array($child, $this->children, true)) {
            $this->children[] = $child;
            $child->parent = $this;
        }
    }

    public function removeChild(FooterItem $child): void
    {
        $this->children = array_filter(
            $this->children,
            fn($c) => $c !== $child
        );
        $child->parent = null;
    }
}
