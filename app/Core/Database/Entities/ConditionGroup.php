<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

/**
 * @Entity
 */
class ConditionGroup
{
    /** @Column(type="primary") */
    public $id;

    /**
     * @HasMany(target="RedirectCondition", cascade=true)
     */
    public $conditions;

    /**
     * @BelongsTo(target="Redirect")
     */
    public $redirect;

    public function __construct()
    {
        $this->conditions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getConditions()
    {
        return $this->conditions;
    }

    public function addCondition(RedirectCondition $condition)
    {
        if (!$this->conditions->contains($condition)) {
            $this->conditions->add($condition);
            $condition->setConditionGroup($this);
        }
    }

    public function getRedirect()
    {
        return $this->redirect;
    }

    public function setRedirect(Redirect $redirect)
    {
        $this->redirect = $redirect;
    }
}