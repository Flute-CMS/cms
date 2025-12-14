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
    indexes: [new Index(columns: ["code"], unique: true)]
)]
#[Behavior\CreatedAt(
    field: 'createdAt',
    column: 'created_at'
)]
#[Behavior\UpdatedAt(
    field: 'updatedAt',
    column: 'updated_at'
)]
class PromoCode extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $code;

    #[Column(type: "integer", nullable: true)]
    public ?int $max_usages = null;

    #[Column(type: "integer", nullable: true)]
    public ?int $max_uses_per_user = null;

    #[Column(type: "enum(amount, percentage)")]
    public string $type;

    #[Column(type: "float")]
    public float $value;

    #[Column(type: "float", nullable: true)]
    public ?float $minimum_amount = null;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $expires_at = null;

    #[HasMany(target: "PromoCodeUsage")]
    public array $usages = [];

    #[ManyToMany(target: "Role", through: "PromoCodeRole", cascade: true)]
    public array $roles = [];

    #[Column(type: "datetime")]
    public \DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    public function addRole(Role $role): void
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
    }

    public function removeRole(Role $role): void
    {
        $this->roles = array_filter(
            $this->roles,
            fn($r) => $r !== $role
        );
    }

    public function clearRoles(): void
    {
        $this->roles = [];
    }

    public function hasRole(string $roleName): bool
    {
        foreach ($this->roles as $role) {
            if ($role->name === $roleName) {
                return true;
            }
        }
        return false;
    }
}
