<?php

namespace Flute\Core\Database;

use Cycle\Database\DatabaseManager;
use Cycle\Migrations\Migrator;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Flute\Core\Database\Concerns\HandlesEntityDirs;
use Flute\Core\Database\Concerns\HandlesMigrations;
use Flute\Core\Database\Concerns\HandlesSchemaCache;
use Flute\Core\Database\Concerns\HandlesSchemaCompilation;
use Flute\Core\Database\Concerns\HandlesSchemaRefresh;
use Flute\Core\Database\Concerns\HandlesSyncDiagnostics;
use Flute\Core\Database\DatabaseManager as FluteDatabaseManager;
use Throwable;

class DatabaseConnection
{
    use HandlesEntityDirs;
    use HandlesMigrations;
    use HandlesSchemaCache;
    use HandlesSchemaCompilation;
    use HandlesSchemaRefresh;
    use HandlesSyncDiagnostics;

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

    private bool $syncTablesFailed = false;

    private static bool $schemaRefreshQueued = false;

    /** @var array<string,bool> */
    private static array $schemaRefreshExtraModules = [];

    public function __construct(?FluteDatabaseManager $databaseManager = null)
    {
        $this->databaseManager = $databaseManager ?? FluteDatabaseManager::getInstance();
        $this->addDir(self::ENTITIES_DIR);

        \Cycle\ActiveRecord\Facade::setContainer(app()->getContainer());
    }

    public function getSchemaNeedsUpdate(): bool
    {
        return $this->schemaNeedsUpdate;
    }

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
            if (!$this->shouldSynchronouslyCompileSchema()) {
                $this->queueDeferredSchemaRefresh();

                $checked = true;

                return;
            }

            $this->recompileOrmSchema(false);
        }

        $checked = true;
    }

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

    public function getDbal(): DatabaseManager
    {
        return $this->dbal;
    }

    protected function ormIntoContainer(): void
    {
        \Cycle\ActiveRecord\Facade::reset();
        \Cycle\ActiveRecord\Facade::setContainer(app()->getContainer());

        app()->bind(ORM::class, $this->orm);
        app()->bind(ORMInterface::class, $this->orm);
    }

    protected function connect(): void
    {
        $this->dbal = $this->databaseManager->getDbal();

        $timingLogger = new \Flute\Core\Database\DatabaseTimingLogger(
            logs('database'),
            (bool) config('database.debug'),
        );
        $this->dbal->setLogger($timingLogger);

        if (!$this->shouldSynchronouslyCompileSchema() && is_file(self::SCHEMA_FILE)) {
            $this->ensureInstalledModuleEntityDirs();

            if (!$this->isSchemaCacheValid()) {
                $this->queueDeferredSchemaRefresh();
            }

            $this->loadCachedSchemaIntoOrm();

            return;
        }

        $this->recompileOrmSchema(true);
    }

    private function shouldSynchronouslyCompileSchema(): bool
    {
        return PHP_SAPI === 'cli';
    }

    private function isDebugMode(): bool
    {
        return defined('FLUTE_DEBUG') && constant('FLUTE_DEBUG') === true;
    }
}
