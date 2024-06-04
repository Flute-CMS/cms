<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

/**
 * @Entity()
 */
class UserSocialNetwork
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string") */
    public $value;

    /** @Column(type="string", nullable=true) */
    public $url;

    /** @Column(type="string", nullable=true) */
    public $name = false;

    /** @Column(type="boolean", default=false) */
    public $hidden = false;

    /**
     * @Column(type="datetime", nullable=true)
     */
    public $linkedAt;

    /** @Column(type="json", nullable=true) */
    public $additional;

    public function __construct()
    {
        $this->linkedAt = new \DateTime();
    }

    /** @BelongsTo(target="SocialNetwork", nullable=false, cascade=true) */
    public $socialNetwork;

    /** @BelongsTo(target="User", nullable=false, cascade=true) */
    public $user;
}