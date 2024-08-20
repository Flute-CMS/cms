<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\Annotated\Annotation\Table;

#[Entity]
#[Table(
    indexes: [
        new Index(columns: ["key"], unique: true)
    ]
)]
class SocialNetwork
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $key;

    #[Column(type: "text")]
    public string $settings;

    #[Column(type: "integer", default: 0)]
    public int $cooldownTime = 0;

    #[Column(type: "boolean", default: true)]
    public bool $allowToRegister;

    #[Column(type: "text")]
    public string $icon; // svg or png or icon

    #[Column(type: "boolean", default: false)]
    public bool $enabled;

    public function getSettings(): array
    {
        return empty($this->settings) ? [] : json_decode($this->settings, true)['keys'];
    }
}