<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

/**
 * @Entity()
 */
class PasswordResetToken
{
    /** @Column(type="primary") */
    public $id;

    /** @BelongsTo(target="User", nullable=false, cascade=true) */
    public $user;

    /** @Column(type="string") */
    public $token;

    /** @Column(type="datetime") */
    public $expiry;
}
