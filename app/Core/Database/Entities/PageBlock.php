<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
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
class PageBlock extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[BelongsTo(target: "Page", nullable: false)]
    public Page $page;

    #[Column(type: "string")]
    public string $widget;

    #[Column(type: "json")]
    public string $gridstack;

    #[Column(type: "json")]
    public string $settings;

    #[ManyToMany(target: "Permission", through: "PageBlockPermission")]
    public array $permissions;

    #[Column(type: "datetime")]
    public \DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    public function getId() : int
    {
        return $this->id;
    }

    public function getPage() : Page
    {
        return $this->page;
    }

    public function setPage(Page $page) : void
    {
        $this->page = $page;
    }

    public function getWidget() : string
    {
        return $this->widget;
    }

    public function setWidget(string $widget) : void
    {
        $this->widget = $widget;
    }

    public function getSettings() : string
    {
        return $this->settings;
    }

    public function setSettings(string $settings) : void
    {
        $this->settings = $settings;
    }

    /**
     * @return array
     */
    public function getPermissions() : array
    {
        return $this->permissions;
    }
}