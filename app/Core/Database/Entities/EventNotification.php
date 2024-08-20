<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity]
class EventNotification
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $event;

    #[Column(type: "string")]
    public string $icon;

    #[Column(type: "string", nullable: true)]
    public ?string $url;

    #[Column(type: "string")]
    public string $title;

    #[Column(type: "string")]
    public string $content;
}
