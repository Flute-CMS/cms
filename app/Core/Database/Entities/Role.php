<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Cycle\ORM\Relation\Pivoted\PivotedCollection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity()
 */
class Role
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string") */
    public $name;

    /** @Column(type="string", nullable=true) */
    public $color;

    /** @Column(type="integer", default=0) */
    public $priority;

    /** @ManyToMany(target="Permission", though="RolePermission") */
    public $permissions;

    public function __construct()
    {
        $this->permissions = new PivotedCollection();
    }

    /**
     * Add a permission to the role.
     *
     * @param Permission $permission The permission to add.
     */
    public function addPermission(Permission $permission): void
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
        }
    }

    /**
     * Exists permission
     *
     * @param Permission $permission The permission to add.
     */
    public function hasPermission(Permission $permission): bool
    {
        return $this->permissions->contains($permission);
    }

    /**
     * Remove a permission from the role.
     *
     * @param Permission $permission The permission to remove.
     */
    public function removePermission(Permission $permission): void
    {
        if ($this->permissions->contains($permission)) {
            $this->permissions->removeElement($permission);
        }
    }
}