<?php

declare(strict_types = 1);

namespace Flute\Core\Support;

/**
 * Automatically detects and fixes directory permission issues.
 *
 * Runs once per hour (cached via a stamp file) and ensures that critical
 * writable directories have correct ownership and group-writable permissions.
 * This prevents the common issue where root (CLI/cron) creates files that
 * www-data (Apache/nginx) cannot overwrite, or vice versa.
 */
class StoragePermissionFixer
{
    private const STAMP_FILE =
        'storage'
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'cache'
            . DIRECTORY_SEPARATOR
            . '.perms_checked';

    private const CHECK_INTERVAL = 3600; // 1 hour

    /**
     * Run the permission check and fix if needed.
     * Designed to be called early in bootstrap — must be fast and never throw.
     */
    public static function ensurePermissions(string $basePath): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return;
        }

        $stampFile = $basePath . self::STAMP_FILE;

        // Skip if checked recently
        if (is_file($stampFile) && ( time() - (int) @filemtime($stampFile) ) < self::CHECK_INTERVAL) {
            return;
        }

        $dirs = [
            $basePath . 'storage',
            $basePath . 'storage' . DIRECTORY_SEPARATOR . 'logs',
            $basePath . 'storage' . DIRECTORY_SEPARATOR . 'app',
            $basePath . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'cache',
            $basePath
                . 'storage'
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'cache'
                . DIRECTORY_SEPARATOR
                . 'locks',
            $basePath . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'views',
            $basePath . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'temp',
            $basePath . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'translations',
            $basePath . 'app' . DIRECTORY_SEPARATOR . 'Modules',
            $basePath . 'app' . DIRECTORY_SEPARATOR . 'Themes',
            $basePath . 'config',
        ];

        $fixed = 0;

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $stat = @stat($dir);
            if ($stat === false) {
                continue;
            }

            // Ensure directory is group-writable (g+w)
            if (( $stat['mode'] & 0o020 ) === 0) {
                $newMode = ( $stat['mode'] | 0o070 ) & 0o7777; // g+rwx for directories
                if (@chmod($dir, $newMode)) {
                    $fixed++;
                }
            }

            // Ensure directory is writable by current process
            if (!is_writable($dir)) {
                @chmod($dir, ( $stat['mode'] | 0o070 ) & 0o7777);
                $fixed++;
            }
        }

        // Fix files in storage/app that might have wrong permissions (ORM schema, cache, etc.)
        $criticalFiles = [
            $basePath . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'orm_schema.php',
            $basePath . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'orm_schema.meta.php',
            $basePath
                . 'storage'
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'cache'
                . DIRECTORY_SEPARATOR
                . 'orm_schema.lock',
            $basePath
                . 'storage'
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'cache'
                . DIRECTORY_SEPARATOR
                . 'helpers.cache.php',
            $basePath
                . 'storage'
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'cache'
                . DIRECTORY_SEPARATOR
                . 'helpers.cache.lock',
        ];

        foreach ($criticalFiles as $file) {
            if (!is_file($file)) {
                continue;
            }

            $perms = @fileperms($file);
            if ($perms === false) {
                continue;
            }

            // Ensure file is group-writable (g+rw)
            if (( $perms & 0o020 ) === 0) {
                @chmod($file, ( $perms | 0o060 ) & 0o7777);
                $fixed++;
            }
        }

        // Update stamp
        @touch($stampFile);
        @chmod($stampFile, 0o664);
    }

    /**
     * Force-fix permissions on storage and cache after module install/update/uninstall.
     * Unlike ensurePermissions(), this runs immediately and recursively fixes lock files.
     */
    public static function fixAfterModuleAction(string $basePath): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return;
        }

        $lockDir =
            $basePath
            . 'storage'
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'cache'
            . DIRECTORY_SEPARATOR
            . 'locks';

        if (is_dir($lockDir)) {
            $files = @scandir($lockDir);
            if ($files !== false) {
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }
                    $path = $lockDir . DIRECTORY_SEPARATOR . $file;
                    if (is_file($path)) {
                        $perms = @fileperms($path);
                        if ($perms !== false && ( $perms & 0o020 ) === 0) {
                            @chmod($path, ( $perms | 0o060 ) & 0o7777);
                        }
                    }
                }
            }
            @chmod($lockDir, 0o775);
        }

        $cacheDir = $basePath . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'cache';
        if (is_dir($cacheDir)) {
            @chmod($cacheDir, 0o775);
        }

        // Invalidate stamp so full check runs on next request
        $stampFile = $basePath . self::STAMP_FILE;
        @unlink($stampFile);
    }

    /**
     * Check for permission problems and return a list of issues.
     * Used by admin dashboard to show warnings.
     *
     * @return array<string, string> path => problem description
     */
    public static function getPermissionProblems(string $basePath): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return [];
        }

        $problems = [];

        $writableDirs = [
            'storage',
            'storage' . DIRECTORY_SEPARATOR . 'logs',
            'storage' . DIRECTORY_SEPARATOR . 'app',
            'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'cache',
            'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'locks',
            'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'views',
            'config',
        ];

        foreach ($writableDirs as $relDir) {
            $dir = $basePath . $relDir;
            if (!is_dir($dir)) {
                continue;
            }
            if (!is_writable($dir)) {
                $problems[$relDir] = 'not_writable';
            }
        }

        $lockDir =
            $basePath
            . 'storage'
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'cache'
            . DIRECTORY_SEPARATOR
            . 'locks';
        if (is_dir($lockDir)) {
            $files = @scandir($lockDir);
            if ($files !== false) {
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }
                    $path = $lockDir . DIRECTORY_SEPARATOR . $file;
                    if (is_file($path) && !is_writable($path)) {
                        $problems['cache/locks/' . $file] = 'lock_not_writable';
                        break;
                    }
                }
            }
        }

        return $problems;
    }
}
