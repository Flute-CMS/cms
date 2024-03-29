<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

/**
 * @Entity()
 */
class DatabaseConnection
{
    /**
     * @Column(type="primary")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $mod; // Нужно для того, чтобы модули могли писать идентификаторы: lr, fps, и т.д.

    /**
     * @Column(type="string")
     */
    public $dbname;

    /** 
     * @Column(type="text", nullable=true) 
     */
    public $additional;

    /**
     * @BelongsTo(target="Server", nullable=false, cascade=true)
     * @var Server
     */
    public $server;

    /**
     * @Column(type="timestamp", default="CURRENT_TIMESTAMP")
     */
    public $created_at;

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }
}