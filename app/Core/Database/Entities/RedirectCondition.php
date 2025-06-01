<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity]
class RedirectCondition extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $type;

    #[Column(type: "string")]
    public string $operator;

    #[Column(type: "string")]
    public string $value;

    #[BelongsTo(target: "ConditionGroup")]
    public ConditionGroup $conditionGroup;

    public function __construct(string $type, string $value, string $operator)
    {
        $this->type = $type;
        $this->value = $value;
        $this->operator = $operator;
    }

    public function setConditionGroup(ConditionGroup $conditionGroup) : void
    {
        $this->conditionGroup = $conditionGroup;
    }
}
