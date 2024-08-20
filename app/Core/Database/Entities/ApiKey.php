<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use DateTime;

#[Entity]
class ApiKey
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $key;

    #[Column(type: "datetime")]
    public DateTime $createdAt;

    #[ManyToMany(target: Permission::class, through: ApiKeyPermission::class)]
    public array $permissions = [];

    public function __construct()
    {
        $this->createdAt = new DateTime();
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
