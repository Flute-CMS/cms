<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\Annotated\Annotation\Table;

#[Entity]
#[Table(
    indexes: [
        new Index(columns: ["key"], unique: true)
    ]
)]
class Theme
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

    #[Column(type: "timestamp", default: "CURRENT_TIMESTAMP")]
    public \DateTimeImmutable $created_at;

    #[HasMany(target: "ThemeSettings")]
    public array $settings = [];

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function addSetting(ThemeSettings $setting): void
    {
        if (!in_array($setting, $this->settings, true)) {
            $this->settings[] = $setting;
        }
    }

    public function removeSetting(ThemeSettings $setting): void
    {
        $this->settings = array_filter(
            $this->settings,
            fn($s) => $s !== $setting
        );
    }

    public function changeSetting(ThemeSettings $setting): void
    {
        $this->removeSetting($setting);
        $this->addSetting($setting);
    }
}