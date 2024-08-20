<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity]
class ConditionGroup
{
    #[Column(type: "primary")]
    public int $id;

    #[HasMany(target: "RedirectCondition", cascade: true)]
    public array $conditions = [];

    #[BelongsTo(target: "Redirect")]
    public Redirect $redirect;

    public function addCondition(RedirectCondition $condition): void
    {
        if (!in_array($condition, $this->conditions, true)) {
            $this->conditions[] = $condition;
            $condition->setConditionGroup($this);
        }
    }
}
