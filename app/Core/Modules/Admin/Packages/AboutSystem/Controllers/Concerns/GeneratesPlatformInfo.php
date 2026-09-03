<?php

namespace Flute\Admin\Packages\AboutSystem\Controllers\Concerns;

use Flute\Admin\Packages\AboutSystem\Helpers\AboutSystemHelper;
use Flute\Core\Database\DatabaseManager;
use Throwable;

trait GeneratesPlatformInfo
{
    protected function generateThemesSection(): string
    {
        $lines = [
            $this->sectionTitle('INSTALLED THEMES'),
        ];

        try {
            $themes = $this->themeManager->getAllThemes();
            $currentTheme = $this->themeManager->getCurrentTheme();

            if (empty($themes)) {
                $lines[] = 'No themes installed.';
            } else {
                foreach ($themes as $theme) {
                    $name = $theme->name ?? 'Unknown';
                    $version = $theme->version ?? 'N/A';
                    $status = $theme->status ?? 'unknown';
                    $isCurrent = $theme->name === $currentTheme ? ' [CURRENT]' : '';

                    $statusIcon = match ($status) {
                        'active' => '[ACTIVE]',
                        'disabled' => '[DISABLED]',
                        default => '[' . strtoupper($status) . ']',
                    };

                    $lines[] = sprintf('  %s %s (v%s)%s', $statusIcon, $name, $version, $isCurrent);
                }
            }
        } catch (Throwable $e) {
            $lines[] = 'Error loading themes: ' . $this->sanitizeErrorMessage($e->getMessage());
        }

        return implode("\n", $lines);
    }

    protected function generateDatabaseSection(): string
    {
        $lines = [
            $this->sectionTitle('DATABASE INFORMATION'),
        ];

        try {
            $dbConfig = config('database.connections.default');
            $driver = $dbConfig->driver ?? 'N/A';
            $driverName = str_replace(['Driver', 'Cycle\\Database\\Driver\\'], '', $driver);

            $lines[] = $this->formatKeyValue('Driver', $driverName);

            $connInfo = $dbConfig->connection ?? null;
            $host = 'N/A';
            $dbName = $dbConfig->database ?? 'N/A';

            if (is_string($connInfo)) {
                $host = $connInfo;
            } elseif (is_object($connInfo)) {
                $host = $connInfo->host ?? 'N/A';

                if (isset($connInfo->port)) {
                    $host .= ':' . $connInfo->port;
                }

                $dbName = $connInfo->database ?? $dbName;
            }

            $lines[] = $this->formatKeyValue('Host', '***');
            $lines[] = $this->formatKeyValue('Database', '***');

            $dbal = app(DatabaseManager::class)->getDbal();
            $database = $dbal->database();
            if ($database) {
                $tables = $database->getTables();
                $lines[] = $this->formatKeyValue('Tables Count', (string) count($tables));
            }
        } catch (Throwable $e) {
            $lines[] = 'Error loading database info: ' . $this->sanitizeErrorMessage($e->getMessage());
        }

        return implode("\n", $lines);
    }

    protected function generateCacheSection(): string
    {
        $lines = [
            $this->sectionTitle('CACHE INFORMATION'),
        ];

        try {
            $cacheConfig = config('cache');
            $driver = $cacheConfig['default'] ?? 'file';

            $lines[] = $this->formatKeyValue('Cache Driver', $driver);

            if ($driver === 'file') {
                $cachePath = storage_path('app/cache');
                if (is_dir($cachePath)) {
                    $lines[] = $this->formatKeyValue('Cache Path', 'storage/app/cache');
                    $lines[] = $this->formatKeyValue('Cache Writable', is_writable($cachePath) ? 'Yes' : 'No');
                }
            }
        } catch (Throwable $e) {
            $lines[] = 'Error loading cache info: ' . $this->sanitizeErrorMessage($e->getMessage());
        }

        return implode("\n", $lines);
    }

    protected function generateComposerSection(): string
    {
        $lines = [
            $this->sectionTitle('COMPOSER PACKAGES'),
        ];

        try {
            $composerLock = path('composer.lock');
            if (file_exists($composerLock)) {
                $lockData = json_decode(file_get_contents($composerLock), true);

                if (!empty($lockData['packages'])) {
                    $lines[] = '';
                    $lines[] = 'Installed Packages (' . count($lockData['packages']) . '):';
                    $lines[] = '';

                    foreach ($lockData['packages'] as $package) {
                        $name = $package['name'] ?? 'unknown';
                        $version = $package['version'] ?? 'N/A';
                        $lines[] = sprintf('  %-40s %s', $name, $version);
                    }
                }

                if (!empty($lockData['packages-dev'])) {
                    $lines[] = '';
                    $lines[] = 'Dev Packages: ' . count($lockData['packages-dev']) . ' (omitted)';
                }
            } else {
                $lines[] = 'composer.lock not found';
            }
        } catch (Throwable $e) {
            $lines[] = 'Error reading composer.lock: ' . $this->sanitizeErrorMessage($e->getMessage());
        }

        return implode("\n", $lines);
    }

    protected function generateDirectoriesSection(): string
    {
        $lines = [
            $this->sectionTitle('DIRECTORY PERMISSIONS & SIZES'),
        ];

        $directories = [
            'storage' => storage_path(),
            'storage/logs' => storage_path('logs'),
            'storage/app' => storage_path('app'),
            'storage/app/cache' => storage_path('app/cache'),
            'storage/app/temp' => storage_path('app/temp'),
            'public/assets' => path('public/assets'),
            'app/Modules' => path('app/Modules'),
            'app/Themes' => path('app/Themes'),
            'config' => path('config'),
        ];

        foreach ($directories as $name => $dirPath) {
            if (is_dir($dirPath)) {
                $writable = is_writable($dirPath) ? 'writable' : 'not writable';
                $readable = is_readable($dirPath) ? 'readable' : 'not readable';
                $size = $this->getDirectorySize($dirPath);
                $perms = substr(sprintf('%o', fileperms($dirPath)), -4);

                $lines[] = sprintf(
                    '  %-25s [%s] %s, %s, %s',
                    $name,
                    $perms,
                    $readable,
                    $writable,
                    AboutSystemHelper::formatBytes($size),
                );
            } else {
                $lines[] = sprintf('  %-25s [NOT EXISTS]', $name);
            }
        }

        return implode("\n", $lines);
    }

    protected function generateConfigSection(): string
    {
        $lines = [
            $this->sectionTitle('CONFIGURATION (sanitized)'),
        ];

        $configKeys = [
            'app.name',
            'app.url',
            'app.env',
            'app.debug',
            'app.timezone',
            'app.locale',
            'app.steam_api',
            'app.mode',
            'app.tips',
            'cache.default',
            'database.default',
            'logging.default',
            'logging.level',
            'mail.driver',
            'mail.host',
            'mail.port',
            'view.cache',
            'view.debug',
            'auth.remember_me',
            'auth.csrf_enabled',
            'auth.security_token',
        ];

        foreach ($configKeys as $key) {
            try {
                $value = config($key);

                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } elseif (is_array($value)) {
                    $value = json_encode($value);
                } elseif (is_null($value)) {
                    $value = 'null';
                } elseif (is_object($value)) {
                    $value = $value::class;
                }

                if ($this->isSensitiveKey($key)) {
                    $value = $value ? '***SET***' : '***NOT SET***';
                }

                $lines[] = $this->formatKeyValue($key, (string) $value);
            } catch (Throwable $e) {
                $lines[] = $this->formatKeyValue($key, 'ERROR: ' . $this->sanitizeErrorMessage($e->getMessage()));
            }
        }

        return implode("\n", $lines);
    }
}
