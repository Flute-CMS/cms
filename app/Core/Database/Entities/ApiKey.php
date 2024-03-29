<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Cycle\ORM\Relation\Pivoted\PivotedCollection;
use DateTime;

/**
 * @Entity()
 */
class ApiKey
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string") */
    public $key;

    /** @Column(type="datetime") */
    public $createdAt;

    /** @ManyToMany(target="Permission", though="ApiKeyPermission") */
    public $permissions;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->permissions = new PivotedCollection();
    }

    /**
     * Add a permission to the API key.
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
     * Check if the API key has a specific permission.
     *
     * @param Permission $permission The permission to check.
     */
    public function hasPermission(Permission $permission): bool
    {
        return $this->permissions->contains($permission);
    }

    /**
     * Remove a permission from the API key.
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