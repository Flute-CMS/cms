<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

/**
 * @Entity()
 */
class WidgetSettings
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string", unique=true) */
    public $name;

    /** @Column(type="string(6000)", nullable=true) */
    public $value;

    /** @Column(type="string", nullable=true) */
    public $description;

    /**
     * @BelongsTo(target="Widget", nullable=false)
     */
    public $widget;

    /**
     * @Column(type = "enum(select,image,radio,checkbox, text)")
     */
    public $type;

    /**
     * Set the value for the setting.
     *
     * @param mixed $value The value to set.
     */
    public function setValue($value): void
    {
        $this->value = json_encode($value);
    }

    /**
     * Get the value for the setting.
     *
     * @return mixed The value of the setting.
     */
    public function getValue()
    {
        return json_decode($this->value, true);
    }
}
