<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;

#[Entity]
class Redirect extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $fromUrl;

    #[Column(type: "string")]
    public string $toUrl;

    #[HasMany(target: "ConditionGroup", cascade: true)]
    public array $conditionGroups = [];

    public function __construct(string $fromUrl, string $toUrl)
    {
        $this->fromUrl = $fromUrl;
        $this->toUrl = $toUrl;
    }

    public function addConditionGroup(ConditionGroup $conditionGroup) : void
    {
        if (!in_array($conditionGroup, $this->conditionGroups, true)) {
            $this->conditionGroups[] = $conditionGroup;
            $conditionGroup->redirect = $this;
        }
    }

    public function removeConditions() : void
    {
        $this->conditionGroups = [];
    }
}
