<?php

namespace Flute\Core\Database;

use Cycle\Annotated\Locator\TokenizerEmbeddingLocator;
use Cycle\Annotated\Locator\TokenizerEntityLocator;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\ORM;
use Cycle\Schema;
use Cycle\Annotated;
use Cycle\Schema\Registry;
use Cycle\Schema\Compiler;
use Cycle\Database\DatabaseManager;
use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\Exception\MigrationException;
use Cycle\Migrations\FileRepository;
use Cycle\Migrations\Migrator;
use Spiral\Tokenizer\Config\TokenizerConfig;
use Spiral\Tokenizer\Tokenizer;
use Spiral\Tokenizer\ClassLocator;
use Flute\Core\Database\DatabaseManager as FluteDatabaseManager;

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
        $dbal = $this->databaseManager->getDbal();

        if (config('database.debug')) {
            $dbal->setLogger(logs('database'));
        }

        $this->dbal = $dbal;

        // Compile the ORM schema
        $this->recompileOrmSchema(true);
    }

    public function rollbackMigrations(string $directory)
    {
        $config = new MigrationConfig([
            'directory' => path($directory),
            'table' => 'migrations',
            'safe' => true,
        ]);

        $migrator = new Migrator($config, $this->dbal, new FileRepository($config));

        $migrator->configure();
        $migrations = $migrator->getMigrations();

        foreach ($migrations as $migration) {
            $migrator->rollback();
        }
    }

    public function runMigrations(string $directory)
    {
        $config = new MigrationConfig([
            'directory' => path($directory),
            'table' => 'migrations',
            'safe' => true,
        ]);

        $migrator = new Migrator($config, $this->dbal, new FileRepository($config));

        $migrator->configure();
        $migrations = $migrator->getMigrations();

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
     * Check if an entity is present in the ORM schema.
     *
     * @param string $entityClass The class name of the entity.
     * @return bool
     */
    protected function isEntityInSchema(string $entityClass): bool
    {
        $ormSchema = $this->orm->getSchema();
        return $ormSchema->defines(lcfirst($entityClass));
    }

    /**
     * Add a directory of entities and recompile the schema if necessary.
     *
     * @param string $directory The directory containing entity classes.
     */
    public function addDir(string $directory): void
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
            $this->recompileOrmSchema();
        }
    }

    /**
     * Get a list of entities from a directory.
     *
     * @param string $directory The directory to scan for entities.
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
     * Recompile the ORM schema.
     *
     * @param bool $cache Whether to use cache or not.
     */
    public function recompileOrmSchema(bool $cache = false): void
    {
        if ($cache && cache()->has(self::CACHE_KEY)) {
            $this->orm = new ORM(new \Cycle\ORM\Factory($this->dbal), cache(self::CACHE_KEY));
            $this->ormIntoContainer();
            return;
        }

        cache()->delete(self::CACHE_KEY);

        $classLocator = (new Tokenizer(new TokenizerConfig([
            'directories' => $this->entitiesDirs,
        ])))->classLocator();

        $schemaArray = $this->compileSchema($classLocator);

        $ormSchema = new \Cycle\ORM\Schema($schemaArray);

        cache()->set(self::CACHE_KEY, $ormSchema, 86400);

        $this->orm = new ORM(new \Cycle\ORM\Factory($this->dbal), $ormSchema);

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
        $embeddingLocator = new TokenizerEmbeddingLocator($classLocator);
        $entityLocator = new TokenizerEntityLocator($classLocator);

        $schemaGenerators = [
            new Schema\Generator\ResetTables(),
            new Annotated\Embeddings($embeddingLocator),
            new Annotated\Entities($entityLocator),
            new Annotated\TableInheritance(),
            new Annotated\MergeColumns(),
            new Schema\Generator\GenerateRelations(),
            new Schema\Generator\GenerateModifiers(),
            new Schema\Generator\ValidateEntities(),
            new Schema\Generator\RenderTables(),
            new Schema\Generator\RenderRelations(),
            new Schema\Generator\RenderModifiers(),
            new Schema\Generator\ForeignKeys(),
            new Annotated\MergeIndexes(),
            new Schema\Generator\GenerateTypecast(),
        ];

        $registry = new Registry($this->dbal);

        return (new Compiler())->compile($registry, $schemaGenerators);
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
     * Get the Cycle Database Manager instance.
     *
     * @return DatabaseManager
     */
    public function getDbal(): DatabaseManager
    {
        return $this->dbal;
    }
}
