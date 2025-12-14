<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\Annotated\Annotation\Table;
use Cycle\ORM\Entity\Behavior;


#[Entity]
#[Table(
    indexes: [
        new Index(columns: ["key"], unique: true)
    ]
)]
#[Behavior\CreatedAt(
    field: 'createdAt',
    column: 'created_at'
)]
#[Behavior\UpdatedAt(
    field: 'updatedAt',
    column: 'updated_at'
)]
class Theme extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string", unique: true)]
    public string $key;

    #[Column(type: "string")]
    public string $name;

    #[Column(type: "string")]
    public string $version;

    #[Column(type: "string")]
    public string $author;

    #[Column(type: "string")]
    public string $description;

    #[Column(type: "enum(active,disabled,notinstalled)", default: "notinstalled")]
    public string $status;

    #[Column(type: "datetime")]
    public \DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    #[HasMany(target: "ThemeSettings")]
    public array $settings = [];

    public function addSetting(ThemeSettings $setting) : void
    {
        if (!in_array($setting, $this->settings, true)) {
            $this->settings[] = $setting;
        }
    }

    public function removeSetting(ThemeSettings $setting) : void
    {
        $this->settings = array_filter(
            $this->settings,
            fn($s) => $s !== $setting
        );
    }

    public function changeSetting(ThemeSettings $setting) : void
    {
        $this->removeSetting($setting);
        $this->addSetting($setting);
    }

    public function getSettings() : array
    {
        return $this->settings;
    }
}