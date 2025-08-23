<?php

use Cycle\ORM\EntityManager;
use Cycle\ORM\ORM;
use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Database\DatabaseManager;

if (!function_exists("dbal")) {
    /**
     * Get the spiral database manager
     * 
     * @return \Cycle\Database\DatabaseManager
     */
    function dbal(): \Cycle\Database\DatabaseManager
    {
        static $instance = null;

        if ($instance === null) {
            $instance = app(DatabaseManager::class)->getDbal();
        }

        return $instance;
    }
}


if (!function_exists("db")) {
    /**
     * Get the current database instance
     * 
     * @param string $connection
     * 
     * @return \Cycle\Database\DatabaseInterface
     */
    function db(string $connection = "default"): \Cycle\Database\DatabaseInterface
    {
        static $database = null;

        if ($database === null) {
            $database = app(DatabaseManager::class);
        }

        return $database->database($connection);
    }
}

if (!function_exists("orm")) {
    /**
     * Get the ORM instance. Returns a fully initialized ORM object.
     * In early bootstrap (installer) DatabaseConnection may not have the
     * ORM initialized yet, so we guard and initialize lazily.
     *
     * @return ORM
     */
    function orm(): ORM
    {
        static $orm = null;

        if ($orm === null) {
            $dbConn = app(DatabaseConnection::class);

            $orm = $dbConn->getOrm();
        }

        return $orm;
    }
}


if (!function_exists("ormdb")) {
    /**
     * Get the orm database instance
     * 
     * @param string|object $entity
     * 
     * @return \Cycle\Database\DatabaseInterface
     */
    function ormdb($entity): \Cycle\Database\DatabaseInterface
    {
        return orm()->getSource($entity)->getDatabase();
    }
}


if (!function_exists("rep")) {
    /**
     * Get the ORM repository instance
     *
     * @param class-string|object $entity
     * @return \Cycle\ORM\RepositoryInterface
     */
    function rep($entity): \Cycle\ORM\RepositoryInterface
    {
        return orm()->getRepository($entity);
    }
}

if (!function_exists("transaction")) {
    /**
     * Run entity in transaction
     * 
     * @param object|array $entites
     * @param string $operation The operation to be performed (persist, delete).
     * @param bool $cascade
     * 
     * @return EntityManager
     * @throws InvalidArgumentException If an unsupported operation is specified.
     */
    function transaction($entity, string $operation = 'persist', bool $cascade = true): EntityManager
    {
        /** @var ORM $orm */
        $orm = orm();

        $transaction = new EntityManager($orm);

        if (!is_array($entity)) {
            $entity = [$entity];
        }

        foreach ($entity as $key => $value) {
            switch ($operation) {
                case 'persist':
                    $transaction->persist($value, $cascade);
                    break;
                case 'persistState':
                    $transaction->persistState($value, $cascade);
                    break;
                case 'delete':
                    $transaction->delete($value, $cascade);
                    break;
                case 'run':
                    $transaction->run();
                    break;
                case 'clean':
                    $transaction->clean();
                    break;
                default:
                    throw new InvalidArgumentException('Unsupported operation: ' . $operation);
            }
        }

        return $transaction;
    }
}
