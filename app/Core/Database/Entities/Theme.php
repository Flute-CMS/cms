<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Relation\Pivoted\PivotedCollection;

/**
 * @Entity()
 * @Table(
 *      indexes={
 *          @Index(columns={"key"}, unique=true)
 *      }
 * )
 */
class Theme
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string", unique=true) */
    public $key;

    /** @Column(type="string") */
    public $name;

    /** @Column(type="string") */
    public $version;

    /** @Column(type="string") */
    public $author;

    /** @Column(type="string") */
    public $description;

    /**
     * @Column(type = "enum(active,disabled,notinstalled)", default = "notinstalled")
     */
    public $status;

    /**
     * @Column(type="timestamp", default="CURRENT_TIMESTAMP")
     */
    public $created_at;

    /** @HasMany(target="ThemeSettings") */
    public $settings;

    public function __construct()
    {
        $this->settings = new PivotedCollection();
        $this->created_at = new \DateTime();
    }

    public function addSetting(ThemeSettings $setting)
    {
        if (!$this->settings->contains($setting)) {
            $this->settings->add($setting);
        }
    }

    public function removeSetting(ThemeSettings $setting)
    {
        if ($this->settings->contains($setting)) {
            $this->settings->removeElement($setting);
        }
    }

    public function changeSetting(ThemeSettings $setting)
    {
        if ($this->settings->contains($setting)) {
            $this->settings->removeElement($setting);
            $this->settings->add($setting);
        }
    }

    public function getSettings()
    {
        return $this->settings;
    }
}