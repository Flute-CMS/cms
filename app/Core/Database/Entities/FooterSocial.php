<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity]
class FooterSocial
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $name;

    #[Column(type: "text")]
    public string $icon;

    #[Column(type: "string")]
    public string $url;
}
