<?php

namespace Flute\Core\Database;

use Cycle\Annotated;
use Cycle\Annotated\Locator\TokenizerEmbeddingLocator;
use Cycle\Annotated\Locator\TokenizerEntityLocator;
use Cycle\Database\DatabaseManager;
use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\Exception\MigrationException;
use Cycle\Migrations\FileRepository;
use Cycle\Migrations\Migrator;
use Cycle\ORM\Entity\Behavior\EventDrivenCommandGenerator;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Exception\SyncException;
use Cycle\Schema\Registry;
use Exception;
use Flute\Core\Cache\SWRQueue;
use Flute\Core\Database\DatabaseManager as FluteDatabaseManager;
use Flute\Core\Database\Entities\Module;
use Spiral\Tokenizer\ClassLocator;
use Spiral\Tokenizer\Config\TokenizerConfig;
use Spiral\Tokenizer\Tokenizer;
use Throwable;

class DatabaseConnection
{
    public const CACHE_KEY = 'database.schema';

    protected const ENTITIES_DIR = BASE_PATH . 'app/Core/Database/Entities';

    protected const SCHEMA_FILE = BASE_PATH . 'storage/app/orm_schema.php';

    protected FluteDatabaseManager $databaseManager;

    protected ORM $orm;

    protected DatabaseManager $dbal;

    protected Migrator $migrator;

    protected array $entitiesDirs = [];

    protected bool $schemaNeedsUpdate = false;

    private static bool $schemaRefreshQueued = false;

    /** @var array<string,bool> */
    private static array $schemaRefreshExtraModules = [];

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
     * Rollback migrations.
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
        static $checked = false;

        if ($checked && isset($this->orm)) {
            return;
        }

        if (!is_installed() && !$ignoreInstalled) {
            return;
        }

        if (!isset($this->orm)) {
            $this->connect();
            $checked = true;

            return;
        }

        if ($this->schemaNeedsUpdate) {
            $this->recompileOrmSchema(false);
        }

        $checked = true;
    }

    /**
     * Recompiling ORM schema with generating migrations.
     *
     * @param bool $cache Use cache or not.
     */
    public function recompileOrmSchema(bool $cache = false): void
    {
        if ($cache && file_exists(self::SCHEMA_FILE)) {
            if (!isset($this->dbal)) {
                $this->dbal = $this->databaseManager->getDbal();
                if (config('database.debug')) {
                    $timingLogger = new \Flute\Core\Database\DatabaseTimingLogger(logs('database'));
                    $this->dbal->setLogger($timingLogger);
                }
            }

            $schemaArray = include self::SCHEMA_FILE;
            $ormSchema = new \Cycle\ORM\Schema($schemaArray);
            $commandGenerator = new EventDrivenCommandGenerator($ormSchema, app()->getContainer());

            $this->orm = new ORM(factory: new \Cycle\ORM\Factory($this->dbal), schema: $ormSchema, commandGenerator: $commandGenerator);
            $this->ormIntoContainer();

            return;
        }

        $lockFile = BASE_PATH . 'storage/app/cache/orm_schema.lock';
        $lockHandle = fopen($lockFile, 'w+');

        if (!$lockHandle) {
            throw new Exception("Failed to open lock file: {$lockFile}");
        }

        // Try non-blocking lock first
        $gotLock = flock($lockHandle, LOCK_EX | LOCK_NB);

        if (!$gotLock) {
            // Another process is compiling - wait for it to finish (with timeout)
            $maxWait = 30; // 30 seconds max wait
            $waited = 0;
            while (!file_exists(self::SCHEMA_FILE) && $waited < $maxWait) {
                usleep(100000); // 100ms
                $waited += 0.1;
            }

            fclose($lockHandle);

            // Use cached schema if available
            if (file_exists(self::SCHEMA_FILE)) {
                $this->recompileOrmSchema(true);
            }

            return;
        }

        try {
            if (!isset($this->dbal)) {
                $this->dbal = $this->databaseManager->getDbal();
                if (config('database.debug')) {
                    $timingLogger = new \Flute\Core\Database\DatabaseTimingLogger(logs('database'));
                    $this->dbal->setLogger($timingLogger);
                }
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

            $content = '<?php return ' . var_export($schemaArray, true) . ';';
            file_put_contents(self::SCHEMA_FILE, $content);

            $commandGenerator = new EventDrivenCommandGenerator($ormSchema, app()->getContainer());

            $this->orm = new ORM(
                factory: new \Cycle\ORM\Factory($this->dbal),
                schema: $ormSchema,
                commandGenerator: $commandGenerator
            );

            $this->ormIntoContainer();

            $this->runMigrations(path('storage/migrations'));

            $this->schemaNeedsUpdate = false;
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            @unlink($lockFile);
        }
    }

    /**
     * Compiling database schema with generating migrations.
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

        try {
            return (new Compiler())->compile($registry, $schemaGenerators);
        } catch (SyncException $e) {
            logs('database')->warning('Schema sync failed, retrying without SyncTables: ' . $e->getMessage());

            $fallbackGenerators = array_filter(
                $schemaGenerators,
                static fn ($generator) => !($generator instanceof Schema\Generator\SyncTables)
            );

            return (new Compiler())->compile(new Registry($this->dbal), $fallbackGenerators);
        }
    }

    /**
     * Getting ORM instance.
     */
    public function getOrm(): ORM
    {
        if (!isset($this->orm)) {
            try {
                $this->connect();
            } catch (Throwable $e) {
                if (function_exists('logs')) {
                    logs('database')->error('Failed to initialize ORM: ' . $e->getMessage());
                }

                throw $e;
            }
        }

        return $this->orm;
    }

    /**
     * Getting DatabaseManager instance.
     */
    public function getDbal(): DatabaseManager
    {
        return $this->dbal;
    }

    /**
     * Force refreshing ORM schema and reloading all entities.
     * Used when there are problems with entity recognition after cache cleanup.
     */
    public function forceRefreshSchema(array $extraModules = []): void
    {
        logs()->info("Force refreshing ORM schema");

        if (file_exists(self::SCHEMA_FILE)) {
            $stale = self::SCHEMA_FILE . '.stale';
            @unlink($stale);
            if (!@rename(self::SCHEMA_FILE, $stale)) {
                @unlink(self::SCHEMA_FILE);
            }
        }

        $this->entitiesDirs = [self::ENTITIES_DIR];

        $moduleKeys = [];

        // Prefer cached modules DB snapshot (supports SWR) to avoid heavy scans/queries.
        try {
            $cached = cache()->get('flute.modules.alldb', []);
            if (is_array($cached)) {
                foreach ($cached as $row) {
                    $key = $row['key'] ?? null;
                    $status = $row['status'] ?? null;
                    if (is_string($key) && $key !== '' && $status !== 'notinstalled') {
                        $moduleKeys[$key] = true;
                    }
                }
            }
        } catch (Throwable) {
        }

        // Fallback to database if cache is unavailable.
        if (empty($moduleKeys)) {
            try {
                $modules = Module::findAll();
                foreach ($modules as $module) {
                    if ($module->status !== 'notinstalled') {
                        $moduleKeys[$module->key] = true;
                    }
                }
            } catch (Throwable) {
            }
        }

        foreach ($extraModules as $k) {
            if (is_string($k) && $k !== '') {
                $moduleKeys[$k] = true;
            }
        }

        foreach (array_keys($moduleKeys) as $moduleKey) {
            $entitiesDir = path("app/Modules/{$moduleKey}/database/Entities");
            if (is_dir($entitiesDir)) {
                $this->entitiesDirs[] = $entitiesDir;
            }
        }

        $this->recompileOrmSchema(false);

        logs()->info("ORM schema refreshed successfully");
    }

    public function forceRefreshSchemaDeferred(array $extraModules = []): void
    {
        if (function_exists('is_cli') && is_cli()) {
            $this->forceRefreshSchema($extraModules);

            return;
        }

        if (function_exists('cache_warmup_mark')) {
            cache_warmup_mark();
        }

        foreach ($extraModules as $k) {
            if (is_string($k) && $k !== '') {
                self::$schemaRefreshExtraModules[$k] = true;
            }
        }

        if (self::$schemaRefreshQueued) {
            return;
        }

        self::$schemaRefreshQueued = true;

        SWRQueue::queue('database.force_refresh_schema', function (): void {
            $modules = array_keys(self::$schemaRefreshExtraModules);

            self::$schemaRefreshExtraModules = [];
            self::$schemaRefreshQueued = false;

            $this->forceRefreshSchema($modules);
        });
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
     * Check if entity is in ORM schema.
     *
     * @param string $entityClass Entity class.
     */
    protected function isEntityInSchema(string $entityClass): bool
    {
        $ormSchema = $this->orm->getSchema();

        return $ormSchema->defines(lcfirst($entityClass));
    }

    /**
     * Getting list of entities from directory.
     *
     * @param string $directory Directory for scanning.
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
     * Getting ClassLocator.
     */
    protected function getClassLocator(): ClassLocator
    {
        return (new Tokenizer(new TokenizerConfig([
            'directories' => $this->entitiesDirs,
        ])))->classLocator();
    }
}
