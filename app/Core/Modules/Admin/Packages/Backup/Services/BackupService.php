<?php

namespace Flute\Admin\Packages\Backup\Services;

use Exception;
use Flute\Core\ModulesManager\ModuleManager;
use Flute\Core\Support\FileUploader;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class BackupService
{
    protected string $backupPath;

    protected ModuleManager $moduleManager;

    public function __construct()
    {
        $this->backupPath = storage_path('backup');
        $this->moduleManager = app(ModuleManager::class);

        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0o755, true);
        }
    }

    /**
     * Get list of all backups (both ZIP files and directories)
     */
    public function getBackups(): array
    {
        $backups = [];

        // Scan ZIP files in root backup directory
        $zipFiles = glob($this->backupPath . '/*.zip');
        foreach ($zipFiles as $file) {
            $filename = basename($file);
            $parts = $this->parseBackupFilename($filename);

            $backups[] = [
                'filename' => $filename,
                'path' => $file,
                'type' => $parts['type'],
                'name' => $parts['name'],
                'date' => $parts['date'],
                'size' => filesize($file),
                'size_formatted' => $this->formatBytes(filesize($file)),
                'is_directory' => false,
            ];
        }

        // Scan subdirectories for directory-based backups
        $subDirs = ['modules', 'themes', 'cms', 'vendor', 'composer'];
        foreach ($subDirs as $subDir) {
            $subPath = $this->backupPath . '/' . $subDir;
            if (!is_dir($subPath)) {
                continue;
            }

            $dirs = glob($subPath . '/*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $dirName = basename($dir);
                $backups[] = [
                    'filename' => $subDir . '/' . $dirName,
                    'path' => $dir,
                    'type' => $this->mapSubDirToType($subDir),
                    'name' => $this->extractNameFromDirBackup($dirName),
                    'date' => $this->extractDateFromDirBackup($dirName, $dir),
                    'size' => $this->getDirectorySize($dir),
                    'size_formatted' => $this->formatBytes($this->getDirectorySize($dir)),
                    'is_directory' => true,
                ];
            }

            // Also check for cms-* directories in root
            if ($subDir === 'cms') {
                $cmsDirs = glob($this->backupPath . '/cms-*', GLOB_ONLYDIR);
                foreach ($cmsDirs as $dir) {
                    $dirName = basename($dir);
                    $backups[] = [
                        'filename' => $dirName,
                        'path' => $dir,
                        'type' => 'cms',
                        'name' => 'core',
                        'date' => $this->extractDateFromDirBackup($dirName, $dir),
                        'size' => $this->getDirectorySize($dir),
                        'size_formatted' => $this->formatBytes($this->getDirectorySize($dir)),
                        'is_directory' => true,
                    ];
                }
            }
        }

        usort($backups, static fn($a, $b) => $b['date'] <=> $a['date']);

        return $backups;
    }

    /**
     * Create backup of a module
     */
    public function backupModule(string $moduleKey): string
    {
        $module = $this->moduleManager->getModule($moduleKey);
        if (!$module) {
            throw new Exception(__('admin-backup.errors.module_not_found'));
        }

        $modulePath = path('app/Modules/' . $moduleKey);
        if (!is_dir($modulePath)) {
            throw new Exception(__('admin-backup.errors.module_path_not_found'));
        }

        $filename = $this->generateBackupFilename('module', $moduleKey);
        $zipPath = $this->backupPath . '/' . $filename;

        $this->createZipFromDirectory($modulePath, $zipPath);

        return $filename;
    }

    /**
     * Create backup of a theme
     */
    public function backupTheme(string $themeKey): string
    {
        $themePath = path('app/Themes/' . $themeKey);
        if (!is_dir($themePath)) {
            throw new Exception(__('admin-backup.errors.theme_path_not_found'));
        }

        $filename = $this->generateBackupFilename('theme', $themeKey);
        $zipPath = $this->backupPath . '/' . $filename;

        $this->createZipFromDirectory($themePath, $zipPath);

        return $filename;
    }

    /**
     * Create backup of entire CMS core
     */
    public function backupCms(): string
    {
        $filename = $this->generateBackupFilename('cms', 'core');
        $zipPath = $this->backupPath . '/' . $filename;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception(__('admin-backup.errors.cannot_create_zip'));
        }

        // Backup core directories
        $directories = [
            'app/Core' => path('app/Core'),
            'app/Helpers' => path('app/Helpers'),
            'config' => path('config'),
            'bootstrap' => path('bootstrap'),
            'i18n' => path('i18n'),
        ];

        foreach ($directories as $prefix => $dir) {
            if (is_dir($dir)) {
                $this->addDirectoryToZip($zip, $dir, $prefix);
            }
        }

        // Also backup root files
        $rootFiles = ['composer.json', 'composer.lock', 'flute'];
        foreach ($rootFiles as $file) {
            $filePath = path($file);
            if (file_exists($filePath)) {
                $zip->addFile($filePath, $file);
            }
        }

        $zip->close();

        return $filename;
    }

    /**
     * Create backup of all modules
     */
    public function backupAllModules(): string
    {
        $filename = $this->generateBackupFilename('modules', 'all');
        $zipPath = $this->backupPath . '/' . $filename;

        $modulesPath = path('app/Modules');
        if (!is_dir($modulesPath)) {
            throw new Exception(__('admin-backup.errors.modules_path_not_found'));
        }

        $this->createZipFromDirectory($modulesPath, $zipPath);

        return $filename;
    }

    /**
     * Create backup of all themes
     */
    public function backupAllThemes(): string
    {
        $filename = $this->generateBackupFilename('themes', 'all');
        $zipPath = $this->backupPath . '/' . $filename;

        $themesPath = path('app/Themes');
        if (!is_dir($themesPath)) {
            throw new Exception(__('admin-backup.errors.themes_path_not_found'));
        }

        $this->createZipFromDirectory($themesPath, $zipPath);

        return $filename;
    }

    /**
     * Create full backup (CMS + all modules + all themes)
     */
    public function backupFull(): string
    {
        $filename = $this->generateBackupFilename('full', 'backup');
        $zipPath = $this->backupPath . '/' . $filename;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception(__('admin-backup.errors.cannot_create_zip'));
        }

        // Backup all important directories
        $directories = [
            'app/Core' => path('app/Core'),
            'app/Helpers' => path('app/Helpers'),
            'app/Modules' => path('app/Modules'),
            'app/Themes' => path('app/Themes'),
            'config' => path('config'),
            'bootstrap' => path('bootstrap'),
            'i18n' => path('i18n'),
            'public/assets' => path('public/assets'),
        ];

        foreach ($directories as $prefix => $dir) {
            if (is_dir($dir)) {
                $this->addDirectoryToZip($zip, $dir, $prefix);
            }
        }

        // Root files
        $rootFiles = ['composer.json', 'composer.lock', 'flute'];
        foreach ($rootFiles as $file) {
            $filePath = path($file);
            if (file_exists($filePath)) {
                $zip->addFile($filePath, $file);
            }
        }

        $zip->close();

        return $filename;
    }

    /**
     * Restore a backup
     */
    public function restoreBackup(string $filename, bool $isDirectory): void
    {
        if ($isDirectory) {
            $this->restoreFromDirectory($filename);
        } else {
            $this->restoreFromZip($filename);
        }

        // Clear cache after restore
        if (function_exists('cache')) {
            cache()->clear();
        }
    }

    /**
     * Delete a backup file or directory
     */
    public function deleteBackup(string $filename, bool $isDirectory = false): bool
    {
        $safeName = basename(str_replace("\0", '', $filename));

        if ($safeName === '' || $safeName === '.' || $safeName === '..') {
            throw new Exception(__('admin-backup.errors.backup_not_found'));
        }

        $path = $this->backupPath . '/' . $safeName;
        $realPath = realpath($path);
        $realBackup = realpath($this->backupPath);

        if (!$realPath || !$realBackup || !str_starts_with($realPath, $realBackup . DIRECTORY_SEPARATOR)) {
            throw new Exception(__('admin-backup.errors.backup_not_found'));
        }

        if ($isDirectory) {
            if (!is_dir($realPath)) {
                throw new Exception(__('admin-backup.errors.backup_not_found'));
            }

            return $this->deleteDirectory($realPath);
        }

        if (!is_file($realPath)) {
            throw new Exception(__('admin-backup.errors.backup_not_found'));
        }

        return unlink($realPath);
    }

    /**
     * Get backup file path for download
     */
    public function getBackupPath(string $filename): string
    {
        $safeName = basename(str_replace("\0", '', $filename));
        $path = $this->backupPath . '/' . $safeName;

        $realPath = realpath($path);
        $realBackup = realpath($this->backupPath);

        if (!$realPath || !$realBackup || !str_starts_with($realPath, $realBackup . DIRECTORY_SEPARATOR)) {
            throw new Exception(__('admin-backup.errors.backup_not_found'));
        }

        return $realPath;
    }

    /**
     * Get list of available modules for backup
     */
    public function getAvailableModules(): array
    {
        return $this->moduleManager->getModules()->toArray();
    }

    /**
     * Get list of available themes for backup
     */
    public function getAvailableThemes(): array
    {
        $themesPath = path('app/Themes');
        $themes = [];

        if (!is_dir($themesPath)) {
            return $themes;
        }

        $dirs = glob($themesPath . '/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $key = basename($dir);
            $themeJson = $dir . '/theme.json';

            $name = $key;
            if (file_exists($themeJson)) {
                $data = json_decode(file_get_contents($themeJson), true);
                $name = $data['name'] ?? $key;
            }

            $themes[] = [
                'key' => $key,
                'name' => $name,
            ];
        }

        return $themes;
    }

    /**
     * Get total size of all backups
     */
    public function getTotalBackupSize(): string
    {
        $total = 0;

        // ZIP files
        $files = glob($this->backupPath . '/*.zip');
        foreach ($files as $file) {
            $total += filesize($file);
        }

        // Directory backups
        $subDirs = ['modules', 'themes', 'cms', 'vendor', 'composer'];
        foreach ($subDirs as $subDir) {
            $subPath = $this->backupPath . '/' . $subDir;
            if (is_dir($subPath)) {
                $total += $this->getDirectorySize($subPath);
            }
        }

        // CMS dirs in root
        $cmsDirs = glob($this->backupPath . '/cms-*', GLOB_ONLYDIR);
        foreach ($cmsDirs as $dir) {
            $total += $this->getDirectorySize($dir);
        }

        return $this->formatBytes($total);
    }

    /**
     * Restore from directory backup
     */
    protected function restoreFromDirectory(string $relativePath): void
    {
        $relativePath = str_replace('\\', '/', $relativePath);
        $relativePath = trim($relativePath, '/');

        if (str_contains($relativePath, '..') || str_contains($relativePath, "\0")) {
            throw new Exception(__('admin-backup.errors.backup_not_found'));
        }

        $sourcePath = $this->backupPath . '/' . $relativePath;

        $realSourcePath = realpath($sourcePath);
        $realBackupPath = realpath($this->backupPath);

        if (
            $realSourcePath === false
            || $realBackupPath === false
            || !str_starts_with($realSourcePath, $realBackupPath)
        ) {
            throw new Exception(__('admin-backup.errors.backup_not_found'));
        }

        if (!is_dir($sourcePath)) {
            throw new Exception(__('admin-backup.errors.backup_not_found'));
        }

        // Determine destination based on path
        $parts = explode('/', $relativePath);
        $type = $parts[0] ?? '';
        $dirName = $parts[1] ?? $parts[0];

        $destination = $this->getRestoreDestination($type, $dirName);

        if (!$destination) {
            throw new Exception(__('admin-backup.errors.cannot_determine_destination'));
        }

        // Create backup of current state before restore
        if (is_dir($destination)) {
            $this->copyDirectory($sourcePath, $destination);
        } else {
            $this->copyDirectory($sourcePath, $destination);
        }
    }

    /**
     * Restore from ZIP backup
     */
    protected function restoreFromZip(string $filename): void
    {
        $zipPath = $this->backupPath . '/' . basename($filename);

        if (!file_exists($zipPath)) {
            throw new Exception(__('admin-backup.errors.backup_not_found'));
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new Exception(__('admin-backup.errors.cannot_open_zip'));
        }

        // Parse filename to determine type
        $parts = $this->parseBackupFilename($filename);
        $type = $parts['type'];
        $name = $parts['name'];

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
            $zip->close();

            throw new Exception(__('admin-backup.errors.backup_not_found'));
        }

        $destination = match ($type) {
            'module' => path('app/Modules/' . $name),
            'theme' => path('app/Themes/' . $name),
            'modules' => path('app/Modules'),
            'themes' => path('app/Themes'),
            'cms', 'full' => path(''),
            default => throw new Exception(__('admin-backup.errors.unknown_backup_type')),
        };

        // For single module/theme, extract to parent and rename
        if (in_array($type, ['module', 'theme'])) {
            $tempDir = sys_get_temp_dir() . '/flute_restore_' . uniqid();
            mkdir($tempDir, 0o755, true);
            $zip->close();

            app(FileUploader::class)->safeExtractZip($zipPath, $tempDir);

            // Find the extracted folder
            $extractedDirs = glob($tempDir . '/*', GLOB_ONLYDIR);
            if (!empty($extractedDirs)) {
                $extractedDir = $extractedDirs[0];

                // Remove old directory if exists
                if (is_dir($destination)) {
                    $this->deleteDirectory($destination);
                }

                // Move extracted content
                rename($extractedDir, $destination);
            }

            // Cleanup temp
            $this->deleteDirectory($tempDir);
        } else {
            // For full/cms/modules/themes - extract with zip-slip protection
            $zip->close();

            app(FileUploader::class)->safeExtractZip($zipPath, $destination);
        }
    }

    protected function generateBackupFilename(string $type, string $name): string
    {
        $date = date('Y-m-d_H-i-s');

        return "{$type}_{$name}_{$date}.zip";
    }

    protected function parseBackupFilename(string $filename): array
    {
        // Format: type_name_date.zip
        $filename = str_replace('.zip', '', $filename);
        $parts = explode('_', $filename);

        $type = $parts[0] ?? 'unknown';
        $name = $parts[1] ?? 'unknown';

        // Parse date from the rest
        $dateParts = array_slice($parts, 2);
        $dateStr = implode('_', $dateParts);
        $date = strtotime(str_replace(['_', '-'], [' ', ':'], $dateStr)) ?: time();

        return [
            'type' => $type,
            'name' => $name,
            'date' => $date,
        ];
    }

    protected function mapSubDirToType(string $subDir): string
    {
        return match ($subDir) {
            'modules' => 'module',
            'themes' => 'theme',
            'vendor' => 'vendor',
            'composer' => 'composer',
            default => $subDir,
        };
    }

    protected function extractNameFromDirBackup(string $dirName): string
    {
        // Format: ModuleName-2024-01-01-123456 or just ModuleName_2024-01-01_12-30-00
        $parts = preg_split('/[-_](?=\d{4}[-_])/', $dirName, 2);

        return $parts[0] ?? $dirName;
    }

    protected function extractDateFromDirBackup(string $dirName, string $path): int
    {
        // Try to extract date from dirname
        if (preg_match('/(\d{4}[-_]\d{2}[-_]\d{2}[-_]\d{2}[-_]?\d{2}[-_]?\d{2})/', $dirName, $matches)) {
            $dateStr = str_replace(['_', '-'], [' ', ':'], $matches[1]);
            // Normalize format: 2024 01 01 12:30:00
            $dateStr = preg_replace(
                '/(\d{4})\s+(\d{2})\s+(\d{2})\s+(\d{2}):?(\d{2}):?(\d{2})/',
                '$1-$2-$3 $4:$5:$6',
                $dateStr,
            );
            $timestamp = strtotime($dateStr);
            if ($timestamp) {
                return $timestamp;
            }
        }

        // Fallback to directory modification time
        return filemtime($path) ?: time();
    }

    protected function getRestoreDestination(string $type, string $dirName): ?string
    {
        $name = $this->extractNameFromDirBackup($dirName);

        return match ($type) {
            'modules' => path('app/Modules/' . $name),
            'themes' => path('app/Themes/' . $name),
            'cms' => path(''),
            'vendor' => path('vendor'),
            'composer' => path(''),
            default => null,
        };
    }

    protected function createZipFromDirectory(string $sourcePath, string $zipPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception(__('admin-backup.errors.cannot_create_zip'));
        }

        $sourcePath = realpath($sourcePath);
        $this->addDirectoryToZip($zip, $sourcePath, basename($sourcePath));

        $zip->close();
    }

    protected function addDirectoryToZip(ZipArchive $zip, string $path, string $prefix): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $prefix . '/' . substr($filePath, strlen($path) + 1);

            // Skip vendor, node_modules, and cache directories
            if (
                str_contains($relativePath, '/vendor/')
                || str_contains($relativePath, '/node_modules/')
                || str_contains($relativePath, '/cache/')
            ) {
                continue;
            }

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    protected function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0o755, true);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            $destPath = $destination . '/' . $iterator->getSubPathName();

            if ($item->isDir()) {
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0o755, true);
                }
            } else {
                copy($item->getRealPath(), $destPath);
            }
        }
    }

    protected function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }

        return rmdir($dir);
    }

    protected function getDirectorySize(string $dir): int
    {
        $size = 0;

        if (!is_dir($dir)) {
            return $size;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
            $dir,
            RecursiveDirectoryIterator::SKIP_DOTS,
        ));

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(( $bytes ? log($bytes) : 0 ) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
