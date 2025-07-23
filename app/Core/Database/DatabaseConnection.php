<?php

namespace Flute\Core\Database;

use Cycle\Annotated\Locator\TokenizerEmbeddingLocator;
use Cycle\Annotated\Locator\TokenizerEntityLocator;
use Cycle\ORM\Entity\Behavior\EventDrivenCommandGenerator;
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
    protected Migrator $migrator;

    public const CACHE_KEY = 'database.schema';
    protected const ENTITIES_DIR = BASE_PATH . 'app/Core/Database/Entities';
    protected array $entitiesDirs = [];
    protected const CACHE_TIME = 99999999; // cache forever
    protected bool $schemaNeedsUpdate = false;

    /**
     * Constructor DatabaseConnection.
     */
    public function __construct(?FluteDatabaseManager $databaseManager = null)
    {
        $this->databaseManager = $databaseManager ?? FluteDatabaseManager::getInstance();
        $this->addDir(self::ENTITIES_DIR);

        \Cycle\ActiveRecord\Facade::setContainer(app()->getContainer());
    }

    /**
     * Add ORM instance to application container.
     */
    protected function ormIntoContainer(): void
    {
        app()->bind(ORM::class, $this->orm);
        app()->bind(ORMInterface::class, $this->orm);
    }

    /**
     * Setting up database connection.
     */
    protected function connect(): void
    {
        $this->dbal = $this->databaseManager->getDbal();

        if (config('database.debug')) {
            $timingLogger = new \Flute\Core\Database\DatabaseTimingLogger(logs('database'));
            $this->dbal->setLogger($timingLogger);
        }

        $this->recompileOrmSchema(true);
    }

    /**
     * Setting up migrations.
     */
    protected function setupMigrations(): void
    {
        $config = new MigrationConfig([
            'directory' => path('storage/migrations'),
            'table' => 'migrations',
            'safe' => true,
        ]);

        $fileRepository = new FileRepository($config);
        $this->migrator = new Migrator($config, $this->dbal, $fileRepository);
        $this->migrator->configure();
    }

    /**
     * Rollback migrations.
     *
     * @param string $directory
     */
    public function rollbackMigrations(string $directory)
    {
        $this->setupMigrations();

        $migrations = $this->migrator->getMigrations();

        foreach ($migrations as $migration) {
            $this->migrator->rollback();
        }
    }

    /**
     * Run migrations.
     *
     * @param string $directory
     * @throws MigrationException
     */
    public function runMigrations(string $directory)
    {
        $this->setupMigrations();

        $migrations = $this->migrator->getMigrations();

        foreach ($migrations as $migration) {
            try {
                while ($this->migrator->run() !== null) {
                }
            } catch (MigrationException $e) {
                $this->migrator->rollback();
                throw $e;
            }
        }
    }

    /**
     * Check if entity is in ORM schema.
     *
     * @param string $entityClass Entity class.
     * @return bool
     */
    protected function isEntityInSchema(string $entityClass): bool
    {
        $ormSchema = $this->orm->getSchema();
        return $ormSchema->defines(lcfirst($entityClass));
    }

    /**
     * Adding entities directory and recompiling schema if needed.
     *
     * @param string $directory Entities directory.
     */
    public function addDir(string $directory): void
    {
        if (!file_exists($directory) || !is_dir($directory)) {
            logs()->debug("Directory does not exist: {$directory}");
            return;
        }

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

        $this->schemaNeedsUpdate = $schemaNeedsUpdate;
    }

    /**
     * Get if schema needs update.
     *
     * @return bool
     */
    public function getSchemaNeedsUpdate(): bool
    {
        return $this->schemaNeedsUpdate;
    }

    /**
     * Recompile ORM schema if needed.
     */
    public function recompileIfNeeded(bool $ignoreInstalled = false): void
    {
        if (!is_installed() && !$ignoreInstalled) {
            return;
        }

        if (!isset($this->orm)) {
            $this->connect();
            return;
        }
        if ($this->schemaNeedsUpdate) {
            $this->recompileOrmSchema(false);
        }
    }

    /**
     * Getting list of entities from directory.
     *
     * @param string $directory Directory for scanning.
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
     * Recompiling ORM schema with generating migrations.
     *
     * @param bool $cache Use cache or not.
     */
    public function recompileOrmSchema(bool $cache = false): void
    {
        if(!isset($this->dbal)) {
            $this->connect();
        }

        if ($cache && cache()->has(self::CACHE_KEY)) {
            $ormSchema = cache(self::CACHE_KEY);
            $commandGenerator = new EventDrivenCommandGenerator($ormSchema, app()->getContainer());

            $this->orm = new ORM(factory: new \Cycle\ORM\Factory($this->dbal), schema: $ormSchema, commandGenerator: $commandGenerator);
            $this->ormIntoContainer();
            return;
        }

        $validDirs = [];

        foreach ($this->entitiesDirs as $dir) {
            if (file_exists($dir) && is_dir($dir)) {
                $validDirs[] = $dir;
            } else {
                logs()->debug("Skipping non-existent entity directory: {$dir}");
            }
        }

        $this->entitiesDirs = $validDirs;

        $classLocator = $this->getClassLocator();

        $schemaArray = $this->compileSchema($classLocator);

        $ormSchema = new \Cycle\ORM\Schema($schemaArray);

        cache()->set(self::CACHE_KEY, $ormSchema, self::CACHE_TIME);

        $commandGenerator = new EventDrivenCommandGenerator($ormSchema, app()->getContainer());

        $this->orm = new ORM(
            factory: new \Cycle\ORM\Factory($this->dbal),
            schema: $ormSchema,
            commandGenerator: $commandGenerator
        );

        $this->ormIntoContainer();

        $this->runMigrations(path('storage/migrations'));

        $this->schemaNeedsUpdate = false;
    }

    /**
     * Compiling database schema with generating migrations.
     *
     * @param ClassLocator $classLocator
     * @return array
     */
    public function compileSchema(ClassLocator $classLocator): array
    {
        $embeddingLocator = new TokenizerEmbeddingLocator($classLocator);
        $entityLocator = new TokenizerEntityLocator($classLocator);

        $schemaGenerators = [
            new Schema\Generator\ResetTables(),             // Переконфигурировать схемы таблиц (удаляет столбцы при необходимости)
            new Annotated\Embeddings($embeddingLocator),    // Распознавание встраиваемых сущностей
            new Annotated\Entities($entityLocator),         // Идентификация аннотированных сущностей
            new Annotated\TableInheritance(),               // Настройка наследования таблиц
            new Annotated\MergeColumns(),                   // Интеграция столбцов из атрибутов
            new Schema\Generator\GenerateRelations(),       // Определение отношений сущностей
            new Schema\Generator\GenerateModifiers(),       // Применение модификаторов схемы
            new Schema\Generator\ValidateEntities(),        // Проверка соответствия сущностей конвенциям
            new Schema\Generator\RenderTables(),            // Создание схем таблиц
            new Schema\Generator\RenderRelations(),         // Установка ключей и индексов для отношений
            new Schema\Generator\RenderModifiers(),         // Реализация модификаторов схемы
            new Schema\Generator\ForeignKeys(),             // Определение внешних ключей
            new Annotated\MergeIndexes(),                   // Интеграция индексов из атрибутов
            // new \Cycle\Schema\Generator\Migrations\GenerateMigrations(
            //     $this->migrator->getRepository(),
            //     $this->migrator->getConfig()
            // ),
            new Schema\Generator\SyncTables(),
            new Schema\Generator\GenerateTypecast(),        // Типизация нестроковых столбцов
        ];

        $registry = new Registry($this->dbal);

        return (new Compiler())->compile($registry, $schemaGenerators);
    }

    /**
     * Getting ClassLocator.
     *
     * @return ClassLocator
     */
    protected function getClassLocator(): ClassLocator
    {
        return (new Tokenizer(new TokenizerConfig([
            'directories' => $this->entitiesDirs,
        ])))->classLocator();
    }

    /**
     * Getting ORM instance.
     *
     * @return ORM
     */
    public function getOrm(): ORM
    {
        return $this->orm;
    }

    /**
     * Getting DatabaseManager instance.
     *
     * @return DatabaseManager
     */
    public function getDbal(): DatabaseManager
    {
        return $this->dbal;
    }

    /**
     * Force refreshing ORM schema and reloading all entities.
     * Used when there are problems with entity recognition after cache cleanup.
     *
     * @return void
     */
    public function forceRefreshSchema(): void
    {
        logs()->info("Force refreshing ORM schema");

        cache()->delete(self::CACHE_KEY);

        $this->entitiesDirs = [self::ENTITIES_DIR];

        $modulesDir = path('app/Modules');
        if (is_dir($modulesDir)) {
            $finder = finder();
            $finder->directories()->in($modulesDir)->depth('== 0');

            foreach ($finder as $moduleDir) {
                $entitiesDir = $moduleDir->getPathname() . '/database/Entities';
                if (is_dir($entitiesDir)) {
                    logs()->info("Adding entities directory: {$entitiesDir}");
                    $this->entitiesDirs[] = $entitiesDir;
                }
            }
        }

        $this->recompileOrmSchema(false);

        logs()->info("ORM schema refreshed successfully");
    }
}
