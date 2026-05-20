<?php

namespace Flute\Core\Database\Concerns;

use Cycle\ORM\Entity\Behavior\EventDrivenCommandGenerator;
use Cycle\ORM\ORM;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

trait HandlesSchemaCache
{
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

        if (!empty($meta['sync_failed'])) {
            $writtenAt = (int) ( $meta['written_at'] ?? 0 );
            $retryInterval = $this->isDebugMode() ? 120 : 300;
            if (( time() - $writtenAt ) >= $retryInterval) {
                return false;
            }
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
                && ( time() - ( $cached['time'] ?? 0 ) ) < ( $this->isDebugMode() ? 30 : 300 )
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

        if ($valid && isset($meta['entity_count'])) {
            $schemaArray = @include self::SCHEMA_FILE;
            if (is_array($schemaArray)) {
                $schemaEntityCount = count($schemaArray);
                $expectedEntityCount = (int) $meta['entity_count'];

                if ($expectedEntityCount > 0 && $schemaEntityCount < ( $expectedEntityCount * 0.8 )) {
                    return false;
                }
            }
        }

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
     * @return array<int,string>
     */
    private function getCachedSchemaDirs(): array
    {
        if (!is_file(self::SCHEMA_META_FILE)) {
            return [];
        }

        $meta = @include self::SCHEMA_META_FILE;
        if (!is_array($meta) || !is_array($meta['dirs'] ?? null)) {
            return [];
        }

        return array_values(array_filter($meta['dirs'], static fn(mixed $dir): bool => is_string($dir) && $dir !== ''));
    }

    /**
     * @param array<int,string> $dirs
     */
    private function writeSchemaMeta(array $dirs, ?int $entityCount = null, bool $syncFailed = false): void
    {
        $dirs = $this->normalizeDirs($dirs);

        $meta = [
            'fingerprint' => $this->computeEntitiesFingerprint($dirs),
            'dirs' => $dirs,
            'written_at' => time(),
            'entity_count' => $entityCount ?? $this->countEntityFiles($dirs),
            'sync_failed' => $syncFailed,
        ];

        $tmp = self::SCHEMA_META_FILE . '.tmp';
        $content = '<?php return ' . var_export($meta, true) . ';';
        @file_put_contents($tmp, $content, LOCK_EX);
        self::ensureGroupWritable($tmp);
        @rename($tmp, self::SCHEMA_META_FILE);
    }

    private function countEntityFiles(array $dirs): int
    {
        $count = 0;
        /** @var list<string> $stringDirs */
        $stringDirs = array_values(array_filter($dirs, static fn(mixed $dir): bool => is_string($dir) && $dir !== ''));

        foreach ($stringDirs as $dir) {
            $resolved = realpath($dir);
            if ($resolved === false || !is_dir($resolved)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                $resolved,
                FilesystemIterator::SKIP_DOTS,
            ));

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
                    $count++;
                }
            }
        }

        return $count;
    }

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
