<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

/**
 * @Entity()
 */
class EventNotification
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string") */
    public $event;

    /** @Column(type="string") */
    public $icon;

    /** @Column(type="string", nullable=true) */
    public $url;

    /** @Column(type="string") */
    public $title;

    /** @Column(type="string") */
    public $content;
}
