<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Relation\Pivoted\PivotedCollection;

/**
 * @Entity(
 *      repository="Flute\Core\Database\Repositories\PageRepository",
 * )
 * @Table(
 *      indexes={
 *          @Index(columns={"route"}, unique=true)
 *      }
 * )
 */
class Page
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string") */
    public $route;

    /** @Column(type="string") */
    public $title;

    /** @Column(type="text", nullable=true) */
    public $description;

    /** @Column(type="string", nullable=true) */
    public $keywords;

    /** @Column(type="string", nullable=true) */
    public $robots;

    /** @Column(type="string", nullable=true) */
    public $og_title;

    /** @Column(type="text", nullable=true) */
    public $og_description;

    /** @Column(type="string", nullable=true) */
    public $og_image;

    /** @HasMany(target="PageBlock") */
    public $blocks;

    /** @ManyToMany(target="Permission", though="PagePermission") */
    public $permissions;

    public function __construct()
    {
        $this->permissions = new PivotedCollection();
        $this->blocks = new PivotedCollection();
    }

    public function addBlock(PageBlock $block)
    {
        if (!$this->blocks->contains($block)) {
            $this->blocks->add($block);
        }
    }

    public function removeBlock(PageBlock $block)
    {
        if ($this->blocks->contains($block)) {
            $this->blocks->removeElement($block);
        }
    }

    public function removeAllBlocks()
    {
        $this->blocks->clear();
    }

    /**
     * Add a permission to the page.
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
     * Remove a permission from the page.
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
