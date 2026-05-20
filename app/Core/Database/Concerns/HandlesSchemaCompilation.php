<?php

namespace Flute\Core\Database\Concerns;

use Cycle\Annotated;
use Cycle\Annotated\Locator\TokenizerEmbeddingLocator;
use Cycle\Annotated\Locator\TokenizerEntityLocator;
use Cycle\ORM\Entity\Behavior\EventDrivenCommandGenerator;
use Cycle\ORM\ORM;
use Cycle\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Exception\SyncException;
use Cycle\Schema\Registry;
use Spiral\Tokenizer\ClassLocator;
use Throwable;

trait HandlesSchemaCompilation
{
    public function recompileOrmSchema(bool $cache = false): void
    {
        if ($cache && file_exists(self::SCHEMA_FILE)) {
            $this->ensureDbal();

            if ($this->isSchemaCacheValid()) {
                $this->loadCachedSchemaIntoOrm();

                return;
            }
        }

        $lockFile = storage_path('app/cache/orm_schema.lock');
        $lockHandle = \Flute\Core\Services\FileLockService::acquireLockWithWait($lockFile, 15.0);

        if ($lockHandle === false) {
            if (file_exists(self::SCHEMA_FILE)) {
                logs()->debug('ORM schema compilation: lock held by another process, using cached schema');
                $this->ensureDbal();
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
                $this->ensureDbal();
                $this->loadCachedSchemaIntoOrm();

                return;
            }
        }

        try {
            $this->ensureDbal();

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
                $prevHandler = set_error_handler(static function (int $errno, string $errstr, string $errfile) use (
                    &$prevHandler,
                ) {
                    if (
                        ( $errno === E_WARNING || $errno === E_NOTICE )
                        && str_contains($errfile, 'cycle' . DIRECTORY_SEPARATOR . 'database')
                    ) {
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
            $this->writeSchemaMeta($this->entitiesDirs, count($schemaArray), $this->syncTablesFailed);
            $this->syncTablesFailed = false;

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
            $this->syncTablesFailed = true;

            $fallbackGenerators = array_filter(
                $schemaGenerators,
                static fn($generator) => !$generator instanceof Schema\Generator\SyncTables,
            );

            return ( new Compiler() )->compile(new Registry($this->dbal), $fallbackGenerators);
        }
    }

    private function ensureDbal(): void
    {
        if (isset($this->dbal)) {
            return;
        }

        $this->dbal = $this->databaseManager->getDbal();
        $timingLogger = new \Flute\Core\Database\DatabaseTimingLogger(
            logs('database'),
            (bool) config('database.debug'),
        );
        $this->dbal->setLogger($timingLogger);
    }
}
