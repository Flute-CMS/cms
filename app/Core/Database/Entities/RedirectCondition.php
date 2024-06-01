<?php


namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

/**
 * @Entity
 */
class RedirectCondition
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string") */
    public $type;

    /** @Column(type="string") */
    public $operator;

    /** @Column(type="string") */
    public $value;

    /**
     * @BelongsTo(target="ConditionGroup")
     */
    public $conditionGroup;

    public function __construct(string $type, string $value, string $operator)
    {
        $this->type = $type;
        $this->value = $value;
        $this->operator = $operator;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getOperator()
    {
        return $this->operator;
    }

    public function setOperator(string $operator)
    {
        $this->operator = $operator;
    }

    public function getConditionGroup()
    {
        return $this->conditionGroup;
    }

    public function setConditionGroup(ConditionGroup $conditionGroup)
    {
        $this->conditionGroup = $conditionGroup;
    }
}
