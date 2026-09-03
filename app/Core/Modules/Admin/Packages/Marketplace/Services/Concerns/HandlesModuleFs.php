<?php

namespace Flute\Admin\Packages\Marketplace\Services\Concerns;

use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

trait HandlesModuleFs
{
    protected function copyDirectory(string $source, string $destination): bool
    {
        if (!is_dir($source)) {
            return false;
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0o755, true);
        }

        $directory = opendir($source);
        if ($directory === false) {
            return false;
        }

        while (( $file = readdir($directory) ) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $file;
            $destinationPath = $destination . '/' . $file;

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destinationPath);
            } else {
                if (copy($sourcePath, $destinationPath)) {
                    chmod($destinationPath, 0o644);
                }
            }
        }

        closedir($directory);

        return true;
    }

    protected function removeDirectory(string $directory): bool
    {
        if (!is_dir($directory)) {
            return false;
        }

        $items = scandir($directory);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($directory);
    }

    protected function keepProcessAlive(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }
    }

    protected function ensureWritableDirectory(string $dir, bool $create): void
    {
        if ($create && !is_dir($dir)) {
            if (!@mkdir($dir, 0o755, true) && !is_dir($dir)) {
                throw new Exception(__('admin-marketplace.messages.directory_not_writable', ['path' => $dir]));
            }
        }

        if (!is_dir($dir) || !is_writable($dir)) {
            throw new Exception(__('admin-marketplace.messages.directory_not_writable', ['path' => $dir]));
        }
    }

    protected function ensureMarketplaceWorkspace(): void
    {
        $this->ensureWritableDirectory($this->tempDir, true);
        $this->ensureWritableDirectory($this->modulesDir, true);
    }

    protected function applyModuleFilesystemAcl(string $root): void
    {
        if (!is_dir($root)) {
            return;
        }

        @chmod($root, 0o755);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                @chmod($file->getPathname(), 0o755);
            } elseif ($file->isFile()) {
                @chmod($file->getPathname(), 0o644);
            }
        }
    }

    protected function invalidatePhpOpcacheUnder(string $root): void
    {
        if (!function_exists('opcache_invalidate') || !is_dir($root)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if (strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            @opcache_invalidate($file->getPathname(), true);
        }
    }

    protected function invalidateComposerAutoloadCaches(): void
    {
        if (!function_exists('opcache_invalidate')) {
            return;
        }

        $dir = path('vendor/composer');
        if (!is_dir($dir)) {
            return;
        }

        $files = [
            'autoload_real.php',
            'autoload_static.php',
            'autoload_classmap.php',
            'autoload_psr4.php',
            'autoload_files.php',
            'autoload_namespaces.php',
            'installed.php',
        ];

        foreach ($files as $name) {
            $full = $dir . DIRECTORY_SEPARATOR . $name;
            if (is_file($full)) {
                @opcache_invalidate($full, true);
            }
        }
    }

    protected function logOpcacheProductionHints(): void
    {
        if (!function_exists('opcache_get_status')) {
            return;
        }

        $status = @opcache_get_status(false);
        if (!is_array($status) || empty($status['opcache_enabled'])) {
            return;
        }

        $validate = ini_get('opcache.validate_timestamps');
        if ($validate === '0' || $validate === false || strtolower((string) $validate) === 'off') {
            logs('marketplace')->warning(
                'OPcache validate_timestamps is off: some PHP-FPM workers may serve stale code until reload. '
                . 'After marketplace install/update, reload php-fpm (or enable validate_timestamps in php.ini).',
            );
        }
    }
}
