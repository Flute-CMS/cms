<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity]
class WidgetSettings
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string", unique: true)]
    public string $name;

    #[Column(type: "string(6000)", nullable: true)]
    public ?string $value = null;

    #[Column(type: "string", nullable: true)]
    public ?string $description = null;

    #[BelongsTo(target: "Widget", nullable: false)]
    public Widget $widget;

    #[Column(type: "enum(select,image,radio,checkbox,text)")]
    public string $type;

    public function setValue($value): void
    {
        $this->value = json_encode($value);
    }

    public function getValue()
    {
        return json_decode($this->value, true);
    }
}
