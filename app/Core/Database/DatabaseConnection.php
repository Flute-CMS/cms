<?php

namespace Flute\Core\Database;

use Cycle\Annotated;
use Cycle\Annotated\Locator\TokenizerEmbeddingLocator;
use Cycle\Annotated\Locator\TokenizerEntityLocator;
use Cycle\Database\Config\DatabaseConfig;
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
use FilesystemIterator;
use Flute\Core\Cache\SWRQueue;
use Flute\Core\Database\DatabaseManager as FluteDatabaseManager;
use Flute\Core\Database\Entities\Module;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spiral\Tokenizer\ClassLocator;
use Spiral\Tokenizer\Config\TokenizerConfig;
use Spiral\Tokenizer\Tokenizer;
use Throwable;

class DatabaseConnection
{
    public const CACHE_KEY = 'database.schema';

    protected const ENTITIES_DIR =
        BASE_PATH
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'Core'
            . DIRECTORY_SEPARATOR
            . 'Database'
            . DIRECTORY_SEPARATOR
            . 'Entities';

    protected const SCHEMA_FILE =
        BASE_PATH . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'orm_schema.php';

    protected const SCHEMA_META_FILE =
        BASE_PATH . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'orm_schema.meta.php';

    protected FluteDatabaseManager $databaseManager;

    protected ORM $orm;

    protected DatabaseManager $dbal;

    protected ?DatabaseManager $migrationDbal = null;

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
                    continue; // each run() executes one pending migration
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
                $timingLogger = new \Flute\Core\Database\DatabaseTimingLogger(
                    logs('database'),
                    (bool) config('database.debug'),
                );
                $this->dbal->setLogger($timingLogger);
            }

            if ($this->isSchemaCacheValid()) {
                $this->loadCachedSchemaIntoOrm();

                return;
            }
        }

        $lockFile = storage_path('app/cache/orm_schema.lock');

        // Use FileLockService for concurrent schema compilation protection
        $lockHandle = \Flute\Core\Services\FileLockService::acquireLockWithWait($lockFile, 15.0);

        if ($lockHandle === false) {
            if (file_exists(self::SCHEMA_FILE)) {
                logs()->debug('ORM schema compilation: lock held by another process, using cached schema');

                if (!isset($this->dbal)) {
                    $this->dbal = $this->databaseManager->getDbal();
                    $timingLogger = new \Flute\Core\Database\DatabaseTimingLogger(
                        logs('database'),
                        (bool) config('database.debug'),
                    );
                    $this->dbal->setLogger($timingLogger);
                }

                $this->loadCachedSchemaIntoOrm();

                return;
            }

            logs()->warning(
                'ORM schema compilation: lock timeout and no cached schema, waiting for compilation to finish',
            );

            $lockHandle = \Flute\Core\Services\FileLockService::acquireLockWithWait($lockFile, 60.0);

            if ($lockHandle === false) {
                logs()->error('ORM schema compilation: failed to acquire lock after extended wait');

                return;
            }

            if (file_exists(self::SCHEMA_FILE)) {
                \Flute\Core\Services\FileLockService::releaseLock($lockHandle);

                if (!isset($this->dbal)) {
                    $this->dbal = $this->databaseManager->getDbal();
                    $timingLogger = new \Flute\Core\Database\DatabaseTimingLogger(
                        logs('database'),
                        (bool) config('database.debug'),
                    );
                    $this->dbal->setLogger($timingLogger);
                }

                $this->loadCachedSchemaIntoOrm();

                return;
            }
        }

        try {
            if (!isset($this->dbal)) {
                $this->dbal = $this->databaseManager->getDbal();
                $timingLogger = new \Flute\Core\Database\DatabaseTimingLogger(
                    logs('database'),
                    (bool) config('database.debug'),
                );
                $this->dbal->setLogger($timingLogger);
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

            $this->ensureInstalledModuleEntityDirs();

            $classLocator = $this->getClassLocator();

            try {
                // Suppress warnings from Cycle ORM schema introspection (e.g. VIEWs
                // returning columns without the expected 'Field' key).
                $prevHandler = set_error_handler(static function (int $errno, string $errstr, string $errfile) use (
                    &$prevHandler,
                ) {
                    if (
                        ( $errno === E_WARNING || $errno === E_NOTICE )
                        && str_contains($errfile, 'cycle' . DIRECTORY_SEPARATOR . 'database')
                    ) {
                        // Convert to harmless return so null doesn't propagate to TypeError
                        return true;
                    }

                    return $prevHandler ? $prevHandler(...func_get_args()) : false;
                });

                try {
                    $schemaArray = $this->compileSchema($classLocator);
                } finally {
                    restore_error_handler();
                }
            } catch (Throwable $compileError) {
                logs('database')->error('Schema compilation failed: ' . $compileError->getMessage());

                $staleFile = self::SCHEMA_FILE . '.stale';
                if (is_file($staleFile)) {
                    logs('database')->warning('Falling back to stale ORM schema');
                    @copy($staleFile, self::SCHEMA_FILE);
                    $this->writeSchemaMeta($this->entitiesDirs);
                    $this->loadCachedSchemaIntoOrm();
                    \Flute\Core\Services\FileLockService::releaseLock($lockHandle);

                    return;
                }

                \Flute\Core\Services\FileLockService::releaseLock($lockHandle);

                throw $compileError;
            }

            $ormSchema = new \Cycle\ORM\Schema($schemaArray);

            $content = '<?php return ' . var_export($schemaArray, true) . ';';
            file_put_contents(self::SCHEMA_FILE, $content);
            self::ensureGroupWritable(self::SCHEMA_FILE);
            $this->writeSchemaMeta($this->entitiesDirs);

            $commandGenerator = new EventDrivenCommandGenerator($ormSchema, app()->getContainer());

            $this->orm = new ORM(
                factory: new \Cycle\ORM\Factory($this->dbal),
                schema: $ormSchema,
                commandGenerator: $commandGenerator,
            );

            $this->ormIntoContainer();

            $this->runMigrations(path('storage/migrations'));

            $this->schemaNeedsUpdate = false;
        } finally {
            \Flute\Core\Services\FileLockService::releaseLock($lockHandle);
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
            new Schema\Generator\SyncTables(),
            new Schema\Generator\GenerateTypecast(),
        ];

        $registry = new Registry($this->dbal);

        try {
            return ( new Compiler() )->compile($registry, $schemaGenerators);
        } catch (SyncException $e) {
            $this->logSyncError($e);

            $fallbackGenerators = array_filter(
                $schemaGenerators,
                static fn($generator) => !$generator instanceof Schema\Generator\SyncTables,
            );

            return ( new Compiler() )->compile(new Registry($this->dbal), $fallbackGenerators);
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
        logs()->info('Force refreshing ORM schema');

        if (file_exists(self::SCHEMA_FILE)) {
            $stale = self::SCHEMA_FILE . '.stale';
            @unlink($stale);
            if (!@rename(self::SCHEMA_FILE, $stale)) {
                @unlink(self::SCHEMA_FILE);
            }
        }

        if (file_exists(self::SCHEMA_META_FILE)) {
            $stale = self::SCHEMA_META_FILE . '.stale';
            @unlink($stale);
            if (!@rename(self::SCHEMA_META_FILE, $stale)) {
                @unlink(self::SCHEMA_META_FILE);
            }
        }

        $schemaFpCache =
            BASE_PATH
            . 'storage'
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'cache'
            . DIRECTORY_SEPARATOR
            . 'schema_fp_cache.php';
        @unlink($schemaFpCache);

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
            $candidates = [
                path("app/Modules/{$moduleKey}/database/Entities"),
                path("app/Modules/{$moduleKey}/Database/Entities"),
            ];

            foreach ($candidates as $entitiesDir) {
                if (is_dir($entitiesDir)) {
                    $this->entitiesDirs[] = $entitiesDir;

                    break;
                }
            }
        }

        $this->recompileOrmSchema(false);

        logs()->info('ORM schema refreshed successfully');
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
     * Log a detailed SyncException with root cause analysis and DB version info.
     */
    protected function logSyncError(SyncException $e): void
    {
        $message = $e->getMessage();
        $previous = $e->getPrevious();
        $rootMessage = $previous ? $previous->getMessage() : $message;

        $context = [
            'exception' => get_class($e),
            'message' => $message,
        ];

        if ($previous) {
            $context['root_exception'] = get_class($previous);
            $context['root_message'] = $rootMessage;
        }

        $diagnostic = $this->diagnoseSyncError($rootMessage);
        if ($diagnostic !== null) {
            $context['diagnostic'] = $diagnostic;
        }

        try {
            $driver = $this->dbal->database()->getDriver();
            $pdo = null;
            if (method_exists($driver, 'getPDO')) {
                $pdo = $driver->getPDO();
            } elseif (method_exists($driver, 'getConnection')) {
                $conn = $driver->getConnection();
                if ($conn instanceof \PDO) {
                    $pdo = $conn;
                }
            }

            if ($pdo) {
                $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
                $context['db_version'] = $version;

                $caps = DatabaseCapabilities::fromPdo($pdo);
                $context['db_server'] = $caps->getServerLabel();
                $context['db_clean_version'] = $caps->getCleanVersion();
                $context['meets_minimum'] = $caps->meetsMinimumVersion();
                $context['supports_datetime_defaults'] = $caps->supportsDatetimeDefaults();
                $context['supports_json'] = $caps->supportsJsonType();
            }
        } catch (Throwable) {
        }

        $level = $diagnostic !== null ? 'error' : 'warning';
        logs('database')->{$level}(
            'Schema sync failed: '
            . $message
            . ( $diagnostic ? ' [Diagnostic: ' . $diagnostic . ']' : '' )
            . ' — retrying without SyncTables',
            $context,
        );
    }

    /**
     * Analyze a sync error message and return a human-readable diagnostic hint.
     */
    protected function diagnoseSyncError(string $message): ?string
    {
        $msg = strtolower($message);

        if (str_contains($msg, '1067') && str_contains($msg, 'invalid default value')) {
            if (preg_match('/[\'"]([a-z_]+)[\'"]/i', $message, $m)) {
                $column = $m[1];
            } else {
                $column = '(unknown column)';
            }

            return sprintf(
                'Column "%s" — DATETIME column cannot have DEFAULT CURRENT_TIMESTAMP. '
                . 'This usually means MySQL < 5.6.5 or MariaDB < 10.0.1. '
                . 'Upgrade your database server or check the sql_mode setting.',
                $column,
            );
        }

        if (str_contains($msg, '1071') && str_contains($msg, 'specified key was too long')) {
            return (
                'Index key too long — this is common with utf8mb4 on MySQL < 5.7.7. '
                . 'Consider upgrading MySQL or using innodb_large_prefix=ON with ROW_FORMAT=DYNAMIC.'
            );
        }

        if (str_contains($msg, 'syntax error') && str_contains($msg, 'json')) {
            return (
                'JSON column type not supported — requires MySQL 5.7.8+ or MariaDB 10.2.7+. '
                . 'Upgrade your database server.'
            );
        }

        if (str_contains($msg, '1452') && str_contains($msg, 'foreign key constraint fails')) {
            return (
                'Foreign key constraint violation during schema sync. '
                . 'There may be orphaned rows in a table that prevent adding a foreign key. '
                . 'Check data integrity or temporarily disable foreign_key_checks.'
            );
        }

        if (str_contains($msg, 'type text/blob/json can not have non empty default value')) {
            return (
                'A TEXT/BLOB/JSON column has a non-empty default value. '
                . 'MySQL does not allow DEFAULT on these types. '
                . 'The column definition in the entity needs to be changed.'
            );
        }

        if (str_contains($msg, '1146') || str_contains($msg, 'table') && str_contains($msg, 'doesn\'t exist')) {
            return 'A referenced table does not exist. Run migrations first or check module dependencies.';
        }

        return null;
    }

    /**
     * Add ORM instance to application container.
     */
    protected function ormIntoContainer(): void
    {
        \Cycle\ActiveRecord\Facade::reset();
        \Cycle\ActiveRecord\Facade::setContainer(app()->getContainer());

        app()->bind(ORM::class, $this->orm);
        app()->bind(ORMInterface::class, $this->orm);
    }

    /**
     * Setting up database connection.
     */
    protected function connect(): void
    {
        $this->dbal = $this->databaseManager->getDbal();

        $timingLogger = new \Flute\Core\Database\DatabaseTimingLogger(
            logs('database'),
            (bool) config('database.debug'),
        );
        $this->dbal->setLogger($timingLogger);

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
        $this->migrator = new Migrator($config, $this->getMigrationDbal(), $fileRepository);
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
        if (!is_dir($directory)) {
            return [];
        }

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
        return ( new Tokenizer(new TokenizerConfig([
            'directories' => $this->entitiesDirs,
        ])) )->classLocator();
    }

    /**
     * Build a dedicated DBAL for migrations to avoid connecting to every configured database.
     * ORM migrations are intended for the primary database only.
     */
    private function getMigrationDbal(): DatabaseManager
    {
        if ($this->migrationDbal) {
            return $this->migrationDbal;
        }

        $config = config('database');
        if (!is_array($config)) {
            $this->migrationDbal = $this->dbal ?? $this->databaseManager->getDbal();

            return $this->migrationDbal;
        }

        $defaultDb = $config['default'] ?? 'default';
        $databases = $config['databases'] ?? [];
        $connections = $config['connections'] ?? $config['drivers'] ?? [];

        $defaultDatabaseConfig = $databases[$defaultDb] ?? null;
        if (!is_array($defaultDatabaseConfig)) {
            $this->migrationDbal = $this->dbal ?? $this->databaseManager->getDbal();

            return $this->migrationDbal;
        }

        $defaultConnectionName =
            $defaultDatabaseConfig['connection'] ?? $defaultDatabaseConfig['write'] ?? $defaultDatabaseConfig['driver']
                ?? $defaultDb;

        $filteredConnections = [];
        if (is_string($defaultConnectionName) && isset($connections[$defaultConnectionName])) {
            $filteredConnections[$defaultConnectionName] = $connections[$defaultConnectionName];
        }

        $filteredConfig = [
            'default' => $defaultDb,
            'aliases' => $config['aliases'] ?? [],
            'databases' => [$defaultDb => $defaultDatabaseConfig],
            'connections' => $filteredConnections,
        ];

        $this->migrationDbal = new DatabaseManager(new DatabaseConfig($filteredConfig));

        return $this->migrationDbal;
    }

    private function loadCachedSchemaIntoOrm(): void
    {
        $schemaArray = include self::SCHEMA_FILE;
        $ormSchema = new \Cycle\ORM\Schema($schemaArray);
        $commandGenerator = new EventDrivenCommandGenerator($ormSchema, app()->getContainer());

        $this->orm = new ORM(
            factory: new \Cycle\ORM\Factory($this->dbal),
            schema: $ormSchema,
            commandGenerator: $commandGenerator,
        );

        $this->ormIntoContainer();
    }

    private function isSchemaCacheValid(): bool
    {
        if (!is_file(self::SCHEMA_META_FILE)) {
            return false;
        }

        $meta = include self::SCHEMA_META_FILE;
        if (!is_array($meta)) {
            return false;
        }

        $dirs = $meta['dirs'] ?? null;
        $expectedFingerprint = $meta['fingerprint'] ?? null;

        if (!is_array($dirs) || !is_string($expectedFingerprint) || $expectedFingerprint === '') {
            return false;
        }

        $expectedDirs = $this->getExpectedSchemaEntityDirs();
        if (!$this->dirsEqual($dirs, $expectedDirs)) {
            return false;
        }

        $cachedFpFile =
            BASE_PATH
            . 'storage'
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'cache'
            . DIRECTORY_SEPARATOR
            . 'schema_fp_cache.php';

        if (is_file($cachedFpFile)) {
            $cached = @include $cachedFpFile;
            if (
                is_array($cached)
                && ( $cached['fingerprint'] ?? '' ) === $expectedFingerprint
                && ( time() - ( $cached['time'] ?? 0 ) ) < ( is_debug() ? 30 : 300 )
            ) {
                return true;
            }
        }

        try {
            $current = $this->computeEntitiesFingerprint($expectedDirs);
        } catch (Throwable) {
            return false;
        }

        $valid = hash_equals($expectedFingerprint, $current);

        if ($valid) {
            $dir = dirname($cachedFpFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0o775, true);
            }
            @file_put_contents(
                $cachedFpFile,
                '<?php return ' . var_export(['fingerprint' => $expectedFingerprint, 'time' => time()], true) . ';',
            );
            self::ensureGroupWritable($cachedFpFile);
        }

        return $valid;
    }

    /**
     * @param array<int,string> $dirs
     */
    private function computeEntitiesFingerprint(array $dirs): string
    {
        $parts = [];

        foreach ($dirs as $dir) {
            if ($dir === '' || !is_string($dir)) {
                continue;
            }

            $resolved = realpath($dir);
            if ($resolved === false || !is_dir($resolved)) {
                $parts[] = "missing:{$dir}";

                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                $resolved,
                FilesystemIterator::SKIP_DOTS,
            ));

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                    continue;
                }

                $real = $fileInfo->getRealPath();
                $parts[] =
                    ( $real !== false ? $real : $fileInfo->getPathname() )
                    . '|'
                    . $fileInfo->getMTime()
                    . '|'
                    . $fileInfo->getSize();
            }
        }

        sort($parts, SORT_STRING);

        return hash('sha256', implode("\n", $parts));
    }

    /**
     * Ensure schema compilation includes installed modules Entities directories.
     */
    private function ensureInstalledModuleEntityDirs(): void
    {
        $expectedDirs = $this->getExpectedSchemaEntityDirs();
        $this->entitiesDirs = $this->normalizeDirs(array_merge($this->entitiesDirs, $expectedDirs));
    }

    /**
     * Get the canonical list of entity directories that must participate in ORM schema.
     *
     * @return array<int,string>
     */
    private function getExpectedSchemaEntityDirs(): array
    {
        $dirs = array_merge([self::ENTITIES_DIR], $this->entitiesDirs);

        $moduleKeys = $this->getInstalledModuleKeys();

        $modulesRoot = path('app/Modules');
        if (is_dir($modulesRoot)) {
            $scanned = @scandir($modulesRoot);
            if (is_array($scanned)) {
                foreach ($scanned as $dir) {
                    if ($dir === '.' || $dir === '..' || $dir === '.disabled') {
                        continue;
                    }
                    if (!in_array($dir, $moduleKeys, true)) {
                        $moduleKeys[] = $dir;
                    }
                }
            }
        }

        foreach ($moduleKeys as $moduleKey) {
            $candidates = [
                path("app/Modules/{$moduleKey}/database/Entities"),
                path("app/Modules/{$moduleKey}/Database/Entities"),
            ];

            foreach ($candidates as $entitiesDir) {
                if (is_dir($entitiesDir)) {
                    $dirs[] = $entitiesDir;

                    break;
                }
            }
        }

        return $this->normalizeDirs($dirs);
    }

    /**
     * @return array<int,string>
     */
    private function getInstalledModuleKeys(): array
    {
        $keys = [];

        // Prefer cached modules DB snapshot (supports SWR) to avoid extra queries during boot.
        try {
            $cached = cache()->get('flute.modules.alldb', []);
            if (is_array($cached)) {
                foreach ($cached as $row) {
                    $key = $row['key'] ?? null;
                    $status = $row['status'] ?? null;
                    if (is_string($key) && $key !== '' && $status !== 'notinstalled') {
                        $keys[$key] = true;
                    }
                }
            }
        } catch (Throwable) {
        }

        if (!empty($keys)) {
            return array_keys($keys);
        }

        // Fallback to DBAL (does not require ORM schema to include modules entities).
        try {
            if (!isset($this->dbal)) {
                $this->dbal = $this->databaseManager->getDbal();
            }

            $rows = $this->dbal
                ->database()
                ->select()
                ->from('modules')
                ->columns('key', 'status')
                ->fetchAll();
            foreach ($rows as $row) {
                $key = $row['key'] ?? null;
                $status = $row['status'] ?? null;
                if (is_string($key) && $key !== '' && $status !== 'notinstalled') {
                    $keys[$key] = true;
                }
            }
        } catch (Throwable) {
        }

        return array_keys($keys);
    }

    /**
     * @param array<int,string> $dirs
     * @return array<int,string>
     */
    private function normalizeDirs(array $dirs): array
    {
        $unique = [];

        foreach ($dirs as $dir) {
            if (!is_string($dir) || $dir === '') {
                continue;
            }

            if (!is_dir($dir)) {
                continue;
            }

            $real = realpath($dir);
            $unique[$real !== false ? $real : $dir] = true;
        }

        $out = array_keys($unique);
        sort($out, SORT_STRING);

        return $out;
    }

    /**
     * @param array<int,mixed> $a
     * @param array<int,mixed> $b
     */
    private function dirsEqual(array $a, array $b): bool
    {
        $na = $this->normalizeDirs(array_map(static fn($v) => is_string($v) ? $v : '', $a));
        $nb = $this->normalizeDirs(array_map(static fn($v) => is_string($v) ? $v : '', $b));

        return $na === $nb;
    }

    /**
     * @param array<int,string> $dirs
     */
    private function writeSchemaMeta(array $dirs): void
    {
        $dirs = $this->normalizeDirs($dirs);

        $meta = [
            'fingerprint' => $this->computeEntitiesFingerprint($dirs),
            'dirs' => $dirs,
            'written_at' => time(),
        ];

        $tmp = self::SCHEMA_META_FILE . '.tmp';
        $content = '<?php return ' . var_export($meta, true) . ';';
        @file_put_contents($tmp, $content, LOCK_EX);
        self::ensureGroupWritable($tmp);
        @rename($tmp, self::SCHEMA_META_FILE);
    }

    /**
     * Ensure a file is group-writable so both root (CLI/cron) and www-data (Apache) can overwrite it.
     */
    private static function ensureGroupWritable(string $path): void
    {
        if (PHP_OS_FAMILY === 'Windows' || !is_file($path)) {
            return;
        }

        $perms = @fileperms($path);
        if ($perms !== false && ( $perms & 0o020 ) === 0) {
            @chmod($path, ( $perms | 0o060 ) & 0o7777);
        }
    }
}
