<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
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
class GlobalPageBlock extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $widget;

    #[Column(type: "json")]
    public string $gridstack;

    #[Column(type: "json")]
    public string $settings;

    #[Column(type: "integer", default: 0)]
    public int $sortOrder = 0;

    #[Column(type: "json", nullable: true)]
    public ?string $excludedPaths = null;

    #[ManyToMany(target: "Permission", through: "GlobalPageBlockPermission")]
    public array $permissions = [];

    #[Column(type: "datetime")]
    public \DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getWidget(): string
    {
        return $this->widget;
    }

    public function setWidget(string $widget): void
    {
        $this->widget = $widget;
    }

    public function getSettings(): string
    {
        return $this->settings;
    }

    public function setSettings(string $settings): void
    {
        $this->settings = $settings;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getExcludedPaths(): ?string
    {
        return $this->excludedPaths;
    }

    public function setExcludedPaths(?string $excludedPaths): void
    {
        $this->excludedPaths = $excludedPaths;
    }

    /**
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }
}
