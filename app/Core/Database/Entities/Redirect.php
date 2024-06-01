<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\ORM\Relation\Pivoted\PivotedCollection;

/**
 * @Entity
 */
class Redirect
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string") */
    public $fromUrl;

    /** @Column(type="string") */
    public $toUrl;

    /**
     * @HasMany(target="ConditionGroup", cascade=true)
     */
    public $conditionGroups;

    public function __construct(string $fromUrl, string $toUrl)
    {
        $this->fromUrl = $fromUrl;
        $this->toUrl = $toUrl;
        $this->conditionGroups = new PivotedCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFromUrl()
    {
        return $this->fromUrl;
    }

    public function setFromUrl($fromUrl)
    {
        $this->fromUrl = $fromUrl;
    }

    public function getToUrl()
    {
        return $this->toUrl;
    }

    public function setToUrl($toUrl)
    {
        $this->toUrl = $toUrl;
    }

    public function getConditions()
    {
        return $this->conditionGroups;
    }

    public function addConditionGroup(ConditionGroup $conditionGroup)
    {
        if (!$this->conditionGroups->contains($conditionGroup)) {
            $this->conditionGroups->add($conditionGroup);
            $conditionGroup->redirect = $this;
        }
    }

    public function removeConditions()
    {
        $this->conditionGroups->clear();
    }
}