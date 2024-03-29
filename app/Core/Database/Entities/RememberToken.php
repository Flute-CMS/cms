<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

/**
 * @Entity()
 */
class RememberToken
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
     * @BelongsTo(target="UserDevice", nullable=false)
     */
    public $userDevice;

    /**
     * @Column(type="string(64)")
     */
    public $token;

    /**
     * @Column(type="datetime")
     */
    public $lastUsedAt;
}
