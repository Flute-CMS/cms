<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;

/**
 * @Entity()
 */
class UserDevice
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
     * @HasMany(target="RememberToken")
     */
    public $rememberTokens;

    /**
     * @Column(type="string")
     */
    public $deviceDetails;

    /**
     * @Column(type="string")
     */
    public $ip;
}