<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTime;

/**
 * @Entity()
 */
class UserActionLog
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
     * @Column(type="string")
     */
    public $action;

    /**
     * @Column(type="string", nullable=true)
     */
    public $details;

    /**
     * @Column(type="string", nullable=true)
     */
    public $url;

    /**
     * @Column(type="datetime")
     */
    public $actionDate;

    public function __construct()
    {
        $this->actionDate = new DateTime();
    }
}
