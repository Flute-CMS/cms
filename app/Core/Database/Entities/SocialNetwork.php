<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;

/**
 * @Entity()
 * @Table(
 *      indexes={
 *          @Index(columns={"key"}, unique=true),
 *      }
 * )
 */
class SocialNetwork
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string") */
    public $key;

    /** @Column(type="text") */
    public $settings;

    /** @Column(type="integer", default=0) */
    public $cooldownTime = 0;

    /** @Column(type="boolean", default=true) */
    public $allowToRegister;

    /** @Column(type="text") */
    public $icon; // svg or png or icon

    /** @Column(type="boolean", default=false) */
    public $enabled;
}