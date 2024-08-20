<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;

#[Entity]
class PromoCode
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $code;

    #[Column(type: "integer")]
    public int $max_usages;

    #[Column(type: "enum(amount, percentage, subtract)")]
    public string $type;

    #[Column(type: "float")]
    public float $value;

    #[Column(type: "datetime")]
    public \DateTimeImmutable $expires_at;

    #[HasMany(target: "PromoCodeUsage")]
    public array $usages = [];
}
