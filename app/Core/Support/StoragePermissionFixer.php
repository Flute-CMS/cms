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
            $basePath . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'views',
            $basePath . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'temp',
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
}
