<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\ManyToMany;

#[Entity]
class Role
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $name;

    #[Column(type: "string", nullable: true)]
    public ?string $color = null;

    #[Column(type: "integer", default: 0)]
    public int $priority = 0;

    #[ManyToMany(target: "Permission", through: "RolePermission")]
    public array $permissions = [];

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
