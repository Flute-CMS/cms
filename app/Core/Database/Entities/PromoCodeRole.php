<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity]
class PromoCodeRole extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[BelongsTo(target: "PromoCode", nullable: false)]
    public PromoCode $promoCode;

    #[BelongsTo(target: "Role", nullable: false)]
    public Role $role;
}
