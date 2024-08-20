<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\Annotated\Annotation\Table;

#[Entity(repository: "Flute\Core\Database\Repositories\WidgetRepository")]
#[Table(
    indexes: [
        new Index(columns: ["loader"], unique: true)
    ]
)]
class Widget
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $loader;

    #[Column(type: "string")]
    public string $name;

    #[Column(type: "string", nullable: true)]
    public ?string $image = null;

    #[Column(type: "boolean", default: false)]
    public bool $lazyload = false;

    #[HasMany(target: "WidgetSettings")]
    public array $settings = [];

    public function addSetting(WidgetSettings $setting): void
    {
        if (!in_array($setting, $this->settings, true)) {
            $this->settings[] = $setting;
        }
    }

    public function removeSetting(WidgetSettings $setting): void
    {
        $this->settings = array_filter(
            $this->settings,
            fn($s) => $s !== $setting
        );
    }

    public function changeSetting(WidgetSettings $setting): void
    {
        $this->removeSetting($setting);
        $this->addSetting($setting);
    }
}
