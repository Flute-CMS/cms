<?php

namespace Flute\Core\Database;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Promise\ProxyFactory;
use Cycle\Schema;
use Cycle\Annotated;
use Cycle\ORM\Factory;
use Cycle\ORM\ORM;
use Cycle\ORM\Schema as ORMSchema;
use Spiral\Database\DatabaseManager;
use Spiral\Migrations\Capsule;
use Spiral\Migrations\Config\MigrationConfig;
use Spiral\Migrations\Exception\MigrationException;
use Spiral\Migrations\FileRepository;
use Spiral\Migrations\Migrator;
use Spiral\Tokenizer;
use Flute\Core\Database\DatabaseManager as FluteDatabaseManager;
use Spiral\Tokenizer\ClassLocator;

class DatabaseConnection
{
    protected FluteDatabaseManager $databaseManager;
    protected ORM $orm;
    protected DatabaseManager $dbal;

    public const CACHE_KEY = 'database.schema';
    protected const ENTITIES_DIR = BASE_PATH . 'app/Core/Database/Entities';
    protected array $entitiesDirs = [];

    /**
     * DatabaseConnection constructor.
     */
    public function __construct(FluteDatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
        $this->addDir(self::ENTITIES_DIR);

        $this->connect();
    }

    /**
     * Add ORM instance into the application container.
     */
    protected function ormIntoContainer(): void
    {
        app()->bind(ORM::class, $this->orm);
        app()->bind(ORMInterface::class, $this->orm);
    }

    /**
     * Establish a database connection.
     */
    protected function connect(): void
    {
        // Get the Database Abstraction Layer (DBAL) from the DatabaseManager
        $dbal = $this->databaseManager->getDbal();

        if (config('database.debug'))
            $dbal->setLogger(logs('database'));

        $this->dbal = $dbal;

        $orm = new ORM(new Factory($this->dbal));

        // Cache the database schema
        $orm->withPromiseFactory(app()->make(ProxyFactory::class));

        $this->recompileOrmSchema($orm, true);
    }

    public function rollbackMigrations(string $directory)
    {
        $config = new MigrationConfig([
            'directory' => path($directory),
            'table' => 'migrations',
            'safe' => true
        ]);

        $migrator = new Migrator($config, $this->dbal, new FileRepository($config));

        $migrator->configure();
        $migrations = $migrator->getMigrations();

        // Последовательное выполнение каждой миграции
        foreach ($migrations as $migration) {
            $migrator->rollback();
        }
    }

    public function runMigrations(string $directory)
    {
        $config = new MigrationConfig([
            'directory' => path($directory), // Указывает директорию миграций
            'table' => 'migrations', // Имя таблицы для отслеживания миграций
            'safe' => true
        ]);

        $migrator = new Migrator($config, $this->dbal, new FileRepository($config));

        $migrator->configure();
        $migrations = $migrator->getMigrations();

        // Последовательное выполнение каждой миграции
        foreach ($migrations as $migration) {
            try {
                $migrator->run();
            } catch (MigrationException $e) {
                $migrator->rollback();
                throw $e;
            }
        }
    }

    /**
     * Проверка наличия сущности в схеме ORM.
     *
     * @param string $entityClass Имя класса сущности.
     * @return bool
     */
    protected function isEntityInSchema(string $entityClass): bool
    {
        $ormSchema = $this->orm->getSchema();
        return $ormSchema->defines(lcfirst($entityClass));
    }

    /**
     * Добавление директории с сущностями и возможная перекомпиляция схемы.
     *
     * @param string $directory Директория с сущностями.
     */
    public function addDir(string $directory)
    {
        $this->entitiesDirs[] = $directory;

        if (!isset($this->orm)) {
            return;
        }

        $newEntities = $this->getEntitiesFromDirectory($directory);
        $schemaNeedsUpdate = false;

        foreach ($newEntities as $entityClass) {

            if (!$this->isEntityInSchema($entityClass)) {
                $schemaNeedsUpdate = true;
                break;
            }
        }

        if ($schemaNeedsUpdate) {
            // logs('database')->info('Schema recompiling...');
            $this->recompileOrmSchema($this->orm);
        }
    }

    /**
     * Получение списка сущностей из директории.
     *
     * @param string $directory Директория с сущностями.
     * @return array
     */
    protected function getEntitiesFromDirectory(string $directory): array
    {
        $finder = finder();
        $finder->files()->in($directory)->name('*.php');

        $entities = [];
        foreach ($finder as $file) {
            $entities[] = $file->getBasename('.php');
        }

        return $entities;
    }

    /**
     * Перекомпиляция схемы ORM.
     */
    public function recompileOrmSchema($orm, bool $cache = false): void
    {
        if ($cache && cache()->has(self::CACHE_KEY)) {
            $this->orm = $orm->with(cache(self::CACHE_KEY));
            $this->ormIntoContainer();

            return;
        }

        cache()->delete(self::CACHE_KEY);

        $classLocator = (new Tokenizer\Tokenizer(new Tokenizer\Config\TokenizerConfig([
            'directories' => $this->entitiesDirs,
        ])))->classLocator();

        $schemaArray = $this->compileSchema($classLocator);

        // Создаем новую схему ORM
        $ormSchema = new ORMSchema($schemaArray);

        cache()->set(self::CACHE_KEY, $ormSchema, 86400);

        // Обновляем схему в текущем экземпляре ORM
        $this->orm = $orm->with($ormSchema);

        // Обновляем ORM в контейнере приложения
        $this->ormIntoContainer();
    }

    /**
     * Compile the database schema.
     *
     * @param ClassLocator $classLocator
     * @return array
     */
    public function compileSchema(ClassLocator $classLocator): array
    {
        $params = [
            new Annotated\Embeddings($classLocator),
            // register annotated embeddings
            new Annotated\Entities($classLocator),
            // register annotated entities
            new Schema\Generator\ResetTables(),
            // re-declared table schemas (remove columns)
            new Annotated\MergeColumns(),
            // register non field columns (table level)
            new Schema\Generator\GenerateRelations(),
            // generate entity relations
            new Schema\Generator\ValidateEntities(),
            // make sure all entity schemas are correct
            new Schema\Generator\RenderTables(),
            // declare table schemas
            new Schema\Generator\RenderRelations(),
            // declare relation keys and indexes
            new Annotated\MergeIndexes(),
            new Schema\Generator\SyncTables(),
            new Schema\Generator\GenerateTypecast(), // typecast non string columns
        ];

        // if( !is_installed() )
        //     $params[] = new Schema\Generator\SyncTables();

        return(new Schema\Compiler())->compile(new Schema\Registry($this->dbal), $params);
    }

    /**
     * Get the ORM instance.
     *
     * @return ORM
     */
    public function getOrm(): ORM
    {
        return $this->orm;
    }

    /**
     * Get the Spiral Database Manager instance.
     *
     * @return DatabaseManager
     */
    public function getDbal(): DatabaseManager
    {
        return $this->dbal;
    }
}