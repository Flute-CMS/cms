<?php

use Cycle\ORM\ORM;
use Cycle\ORM\RepositoryInterface;
use Cycle\ORM\Transaction;
use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Database\DatabaseManager;
use Spiral\Database\DatabaseInterface;

if( !function_exists("dbal") )
{
    /**
     * Get the spiral database manager
     * 
     * @return \Spiral\Database\DatabaseManager
     */
    function dbal() : \Spiral\Database\DatabaseManager
    {
        /** @var DatabaseManager $db */
        $db = app(DatabaseManager::class);

        return $db->getDbal();
    }
}

if( !function_exists("db") )
{
    /**
     * Get the current database instance
     * 
     * @param string $connection
     * 
     * @return DatabaseInterface
     */
    function db( string $connection = "default" ) : DatabaseInterface
    {
        return app(DatabaseManager::class)->database($connection);
    }
}

if( !function_exists("orm") )
{
    /**
     * Get the orm instance
     * 
     * @return ORM
     */
    function orm() : ORM
    {
        /** @var ORM $orm */
        $orm = app(DatabaseConnection::class)->getOrm();

        return $orm;
    }
}

if( !function_exists("ormdb") )
{
    /**
     * Get the orm database instance
     * 
     * @param string|object $entity
     * 
     * @return DatabaseInterface
     */
    function ormdb( $entity ) : DatabaseInterface
    {
        /** @var ORM $orm */
        $orm = orm();

        return $orm->getSource($entity)->getDatabase();
    }
}

if( !function_exists("rep") )
{
    /**
     * Get the orm repository instance
     * 
     * @param string|object $entity
     * 
     * @return RepositoryInterface
     */
    function rep( $entity ) : RepositoryInterface
    {
        /** @var ORM $orm */
        $orm = orm();

        return $orm->getRepository($entity);
    }
}

if( !function_exists("transaction") )
{
    /**
     * Run entity in transaction
     * 
     * @param object|array $entites
     * @param string $operation The operation to be performed (persist, delete).
     * @param string $mode
     * 
     * @return Transaction
     * @throws InvalidArgumentException If an unsupported operation is specified.
     */
    function transaction($entity, string $operation = 'persist', string $mode = Transaction::MODE_CASCADE) : Transaction
    {
        /** @var ORM $orm */
        $orm = orm();

        $transaction = new Transaction($orm);

        // Сделано чтобы не возникало проблем с циклом
        if( !is_array($entity) )
            $entity = [$entity];

        foreach ($entity as $key => $value) {
            switch ($operation) {
                case 'persist':
                    $transaction->persist($value, $mode);
                break;
                case 'delete':
                    $transaction->delete($value, $mode);
                break;
                default:
                    throw new InvalidArgumentException('Unsupported operation: ' . $operation);
            }
        }

        return $transaction;
    }
}
