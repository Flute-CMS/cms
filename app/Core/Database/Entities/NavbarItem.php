<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\ORM\Relation\Pivoted\PivotedCollection;

/**
 * @Entity()
 */
class NavbarItem
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string") */
    public $title;

    /** @Column(type="string", nullable=true) */
    public $url;

    /** @Column(type="boolean", default=false) */
    public $new_tab = false;

    /** @Column(type="string", nullable=true) */
    public $icon;

    /** @Column(type="integer") */
    public $position = 0;

    /** @Column(type="boolean", default=false) */
    public $visibleOnlyForGuests = false;

    /** @Column(type="boolean", default=false) */
    public $visibleOnlyForLoggedIn = false;

    /** @BelongsTo(target="NavbarItem", nullable=true, innerKey="parent_id") */
    public $parent;

    /** @HasMany(target="NavbarItem", nullable=true, outerKey="parent_id") */
    public $children;

    /** @ManyToMany(target="Role", though="NavbarItemRole") */
    public $roles;

    public function __construct()
    {
        $this->roles = new PivotedCollection();
        $this->children = new PivotedCollection();
    }

    /**
     * Add a role to the navbar item.
     *
     * @param Role $role The role to add.
     */
    public function addRole(Role $role): void
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }
    }

    public function clearRoles(): void
    {
        $this->roles->clear();
    }

    /**
     * has a role
     *
     * @param Role $role The role to add.
     */
    public function hasRole(Role $role): bool
    {
        return $this->roles->contains($role);
    }

    /**
     * Remove a role from the navbar item.
     *
     * @param Role $role The role to remove.
     */
    public function removeRole(Role $role): void
    {
        if ($this->roles->contains($role)) {
            $this->roles->removeElement($role);
        }
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