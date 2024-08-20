<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\Annotated\Annotation\Table;

#[Entity]
#[Table(
    indexes: [
        new Index(columns: ["name"], unique: true),
        new Index(columns: ["adapter"], unique: true)
    ]
)]
class PaymentGateway
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string", unique: true)]
    public string $name;

    #[Column(type: "string", unique: true)]
    public string $adapter;

    #[Column(type: "boolean", default: false)]
    public bool $enabled;

    #[Column(type: "text")]
    public string $additional;
}
