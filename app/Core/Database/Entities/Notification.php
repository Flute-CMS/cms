<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

/**
 * @Entity()
 */
class Notification
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string") */
    public $icon;

    /** @Column(type="string", nullable=true) */
    public $url;

    /** @Column(type="string") */
    public $title;

    /** @Column(type="string") */
    public $content;

    /** @Column(type="boolean", default=false) */
    public $viewed = false;

    /** @Column(type="timestamp") */
    public $created_at;

    /** @BelongsTo(target="User", nullable=false, cascade=true) */
    public $user;

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }
}