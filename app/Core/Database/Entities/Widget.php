<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Relation\Pivoted\PivotedCollection;

/**
 * @Entity(
 *      repository="Flute\Core\Database\Repositories\WidgetRepository",
 * )
 * @Table(
 *      indexes={
 *          @Index(columns={"loader"}, unique=true)
 *      }
 * )
 */
class Widget
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string") */
    public $loader;

    /** @Column(type="string") */
    public $name;

    /** @Column(type="string", nullable=true) */
    public $image;

    /** @Column(type="boolean", default="false") */
    public $lazyload;

    /** @HasMany(target="WidgetSettings") */
    public $settings;

    public function __construct()
    {
        $this->settings = new PivotedCollection();
    }

    public function addSetting(WidgetSettings $setting)
    {
        if (!$this->settings->contains($setting)) {
            $this->settings->add($setting);
        }
    }

    public function removeSetting(WidgetSettings $setting)
    {
        if ($this->settings->contains($setting)) {
            $this->settings->removeElement($setting);
        }
    }

    public function changeSetting(WidgetSettings $setting)
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