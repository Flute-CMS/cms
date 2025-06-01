<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use DateTimeImmutable;
use Cycle\ORM\Entity\Behavior;

#[Entity]
#[Behavior\CreatedAt(
    field: 'createdAt',
    column: 'created_at'
)]
#[Behavior\UpdatedAt(
    field: 'updatedAt',
    column: 'updated_at'
)]
class ApiKey extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $key;

    #[Column(type: "string")]
    public string $name;

    #[Column(type: "datetime")]
    public DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;

    #[Column(type: "datetime", nullable: true)]
    public ?DateTimeImmutable $lastUsedAt = null;

    #[ManyToMany(target: Permission::class, through: ApiKeyPermission::class)]
    public array $permissions = [];

    public function getPermissions(): array
    {
        return array_map(function (Permission $permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'description' => $permission->desc
            ];
        }, $this->permissions);
    }

    public function addPermission(Permission $permission): void
    {
        if (!in_array($permission, $this->permissions, true)) {
            $this->permissions[] = $permission;
        }
    }

    public function hasPermissionByName(string $permissionName): bool
    {
        if ($permissionName !== 'admin.boss' && $this->hasPermissionByName('admin.boss')) {
            return true;
        }

        return collect($this->permissions)->contains(function (Permission $permission) use ($permissionName) {
            return $permission->name === $permissionName;
        });
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

    public function updateLastUsed(): void
    {
        $this->lastUsedAt = new DateTimeImmutable();
        $this->saveOrFail();
    }
}
