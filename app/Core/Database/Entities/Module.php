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
 *          @Index(columns={"key"}, unique=true)
 *      }
 * )
 */
class Module
{
    /**
     * @Column(type="primary")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $key;

    /**
     * @Column(type="string")
     */
    public $name;

    /**
     * @Column(type="string", nullable=true)
     */
    public $description;

    /**
     * @Column(type="string", nullable=true)
     */
    public $installedVersion;

    /**
     * @Column(type = "enum(active,disabled,notinstalled)", default = "notinstalled")
     */
    public $status;

    /**
     * @Column(type="timestamp", default="CURRENT_TIMESTAMP")
     */
    public $created_at;

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }
}