<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\Annotated\Annotation\Table;

#[Entity]
#[Table(
    indexes: [
        new Index(columns: ["key"], unique: true),
        new Index(columns: ["theme_id"])
    ]
)]
class ThemeSettings extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string", unique: true)]
    public string $key;

    #[Column(type: "string")]
    public string $name;

    #[Column(type: "string")]
    public string $value;

    #[Column(type: "string", nullable: true)]
    public ?string $description = null;

    #[BelongsTo(target: "Theme", nullable: false)]
    public Theme $theme;
}
