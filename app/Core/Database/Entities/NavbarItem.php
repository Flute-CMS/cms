<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
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
class NavbarItem extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $title;

    #[Column(type: "string", nullable: true)]
    public ?string $description = null;

    #[Column(type: "string", nullable: true)]
    public ?string $url = null;

    #[Column(type: "boolean", default: false)]
    public bool $new_tab = false;

    #[Column(type: "text", nullable: true)]
    public ?string $icon = null;

    #[Column(type: "integer")]
    public int $position = 0;

    #[Column(type: "boolean", default: false)]
    public bool $visibleOnlyForGuests = false;

    #[Column(type: "boolean", default: false)]
    public bool $visibleOnlyForLoggedIn = false;

    #[Column(type: "enum(all,desktop,mobile)", default: "all")]
    public string $visibility = 'all';

    #[BelongsTo(target: "NavbarItem", nullable: true, innerKey: "parent_id")]
    public ?NavbarItem $parent = null;

    #[HasMany(target: "NavbarItem", nullable: true, outerKey: "parent_id")]
    public array $children = [];

    #[ManyToMany(target: "Role", through: "NavbarItemRole")]
    public array $roles = [];

    #[Column(type: "datetime")]
    public \DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    public function addRole(Role $role) : void
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
    }

    public function clearRoles() : void
    {
        $this->roles = [];
    }

    public function hasRole(Role $role) : bool
    {
        return in_array($role, $this->roles, true);
    }

    public function removeRole(Role $role) : void
    {
        $this->roles = array_filter(
            $this->roles,
            fn($r) => $r !== $role
        );
    }

    public function addChild(NavbarItem $child) : void
    {
        if (!in_array($child, $this->children, true)) {
            $this->children[] = $child;
            $child->parent = $this;
        }
    }

    public function removeChild(NavbarItem $child) : void
    {
        $this->children = array_filter(
            $this->children,
            fn($c) => $c !== $child
        );
        $child->parent = null;
    }
}
