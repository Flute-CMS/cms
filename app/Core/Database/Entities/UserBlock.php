<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTime;

/**
 * @Entity()
 */
class UserBlock
{
    /**
     * @Column(type="primary")
     */
    public $id;

    /**
     * @BelongsTo(target="User", nullable=false)
     */
    public $user;

    /**
     * @BelongsTo(target="User", nullable=false)
     */
    public $blockedBy;

    /**
     * @Column(type="string")
     */
    public $reason;

    /**
     * @Column(type="datetime")
     */
    public $blockedFrom;

    /**
     * @Column(type="datetime", nullable=true)
     */
    public $blockedUntil;

    public function __construct()
    {
        $this->blockedFrom = new DateTime();
    }
}