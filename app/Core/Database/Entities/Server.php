<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\ORM\Relation\Pivoted\PivotedCollection;

/**
 * @Entity()
 */
class Server
{
    /**
     * @Column(type="primary")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $name;

    /**
     * @Column(type="string")
     */
    public $ip;

    /**
     * @Column(type="int")
     */
    public $port;

    /**
     * @Column(type="string")
     */
    public $mod;

    /**
     * @Column(type="string", nullable=true)
     */
    public $rcon;

    /**
     * @Column(type="string", nullable=true)
     */
    public $display_ip;

    /**
     * @HasMany(target="DatabaseConnection", cascade=true, nullable=true)
     * @var DatabaseConnection[]|null
     */
    public $dbconnections;

    /**
     * @Column(type="timestamp", default="CURRENT_TIMESTAMP")
     */
    public $created_at;

    /** 
     * @Column(type="boolean", default=true) 
     */
    public $enabled;

    public function __construct()
    {
        $this->dbconnections = new PivotedCollection();
        $this->created_at = new \DateTime();
    }

    public function getDbConnection(string $mod)
    {
        foreach ($this->dbconnections as $dbConnection) {
            if ($dbConnection->mod === $mod) {
                return $dbConnection;
            }
        }
        return null;
    }
}
