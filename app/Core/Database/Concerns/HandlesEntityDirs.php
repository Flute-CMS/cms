<?php

namespace Flute\Core\Database\Concerns;

use Spiral\Tokenizer\ClassLocator;
use Spiral\Tokenizer\Config\TokenizerConfig;
use Spiral\Tokenizer\Tokenizer;
use Throwable;

trait HandlesEntityDirs
{
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

    protected function isEntityInSchema(string $entityClass): bool
    {
        $ormSchema = $this->orm->getSchema();

        return $ormSchema->defines(lcfirst($entityClass));
    }

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

    protected function getClassLocator(): ClassLocator
    {
        return ( new Tokenizer(new TokenizerConfig([
            'directories' => $this->entitiesDirs,
        ])) )->classLocator();
    }

    private function ensureInstalledModuleEntityDirs(): void
    {
        $expectedDirs = $this->getExpectedSchemaEntityDirs();
        $this->entitiesDirs = $this->normalizeDirs(array_merge($this->entitiesDirs, $expectedDirs));
    }

    /**
     * @return array<int,string>
     */
    private function getExpectedSchemaEntityDirs(): array
    {
        $dirs = array_merge([self::ENTITIES_DIR], $this->entitiesDirs);

        $moduleKeys = $this->getInstalledModuleKeys();

        if (empty($moduleKeys) && !$this->shouldSynchronouslyCompileSchema()) {
            $cachedDirs = $this->getCachedSchemaDirs();
            if ($cachedDirs !== []) {
                return $this->normalizeDirs(array_merge($dirs, $cachedDirs));
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

        if (!$this->shouldSynchronouslyCompileSchema()) {
            return [];
        }

        try {
            if (!isset($this->dbal)) {
                $this->dbal = $this->databaseManager->getDbal();
            }

            $database = $this->dbal->database();
            $select = $database->select()->from('modules')->columns('key', 'status');
            $rows = $select->fetchAll();
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
}
