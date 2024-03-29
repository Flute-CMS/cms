<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

/**
 * @Entity()
 */
class FooterSocial
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string") */
    public $name;

    /** @Column(type="text") */
    public $icon;

    /** @Column(type="string") */
    public $url;
}