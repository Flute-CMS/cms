<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\ORM\Relation\Pivoted\PivotedCollection;

/**
 * @Entity()
 */
class FooterItem
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string") */
    public $title;

    /** @Column(type="string", nullable=true) */
    public $url;

    /** @Column(type="boolean", default=false) */
    public $new_tab;

    /** @Column(type="integer", default=0) */
    public $position = 0;

    /** @BelongsTo(target="FooterItem", nullable=true, innerKey="parent_id") */
    public $parent;

    /** @HasMany(target="FooterItem", nullable=true, outerKey="parent_id") */
    public $children;

    public function __construct()
    {
        $this->children = new PivotedCollection();
    }

    /**
     * Add a child to the navbar item.
     *
     * @param NavbarItem $child The child to add.
     */
    public function addChild(NavbarItem $child): void
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->parent = $this;
        }
    }

    /**
     * Remove a child from the navbar item.
     *
     * @param NavbarItem $child The child to remove.
     */
    public function removeChild(NavbarItem $child): void
    {
        if ($this->children->contains($child)) {
            $this->children->removeElement($child);
            $child->parent = null;
        }
    }
}