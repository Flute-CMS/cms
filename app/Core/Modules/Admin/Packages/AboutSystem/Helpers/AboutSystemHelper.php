<?php

namespace Flute\Admin\Packages\AboutSystem\Helpers;

use Flute\Core\App;

class AboutSystemHelper
{
    /**
     * Get system information
     */
    public static function getSystemInfo(): array
    {
        return [
            'version' => App::VERSION,
            'author' => 'Flames',
            'project_link' => 'https://github.com/Flute-CMS/cms',
            'license' => 'GNU General Public License v3.0',
            'framework' => 'Flute CMS',
        ];
    }

    /**
     * Get PHP information
     */
    public static function getPhpInfo(): array
    {
        $info = [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . ' seconds',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ];

        if (extension_loaded('Zend OPcache') || extension_loaded('opcache')) {
            if (function_exists('opcache_get_status')) {
                $opcacheStatus = @opcache_get_status(false);
                if ($opcacheStatus !== false) {
                    $info['opcache'] = $opcacheStatus['opcache_enabled'] ? 'Enabled' : 'Disabled';
                } else {
                    $info['opcache'] = 'Disabled';
                }
            } else {
                $info['opcache'] = 'Disabled';
            }
        } else {
            $info['opcache'] = 'Not Loaded';
        }

        if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
            if (extension_loaded('Zend OPcache') || extension_loaded('opcache')) {
                $jitEnabled = false;
                if (function_exists('opcache_get_status')) {
                    $status = @opcache_get_status(false);
                    if ($status !== false && isset($status['jit']['buffer_size']) && $status['jit']['buffer_size'] > 0) {
                        $jitEnabled = true;
                    }
                }
                if (!$jitEnabled) {
                    $jitEnabled = (int) ini_get('opcache.jit_buffer_size') > 0;
                }
                $info['jit'] = $jitEnabled ? 'Enabled' : 'Disabled';
            } else {
                $info['jit'] = 'Not Available';
            }
        }

        return $info;
    }

    /**
     * Get server information, включая отформатированные данные по диску.
     */
    public static function getServerInfo(): array
    {
        $path = BASE_PATH;

        $diskTotal = disk_total_space($path);
        $diskFree = disk_free_space($path);
        $diskUsed = $diskTotal - $diskFree;
        $diskPct = $diskTotal > 0 ? round($diskUsed / $diskTotal * 100) : 0;

        return [
            'operating_system' => PHP_OS,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database' => self::detectDatabaseDriver(),
            'server_protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
            'server_port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
            'disk_total_space' => self::formatBytes($diskTotal),
            'disk_free_space' => self::formatBytes($diskFree),
            'disk_used_space' => self::formatBytes($diskUsed),
            'disk_usage_percent' => $diskPct . '%',
        ];
    }

    /**
     * Get CPU load and system RAM usage.
     */
    public static function getResourceUsage(): array
    {
        $loads = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];

        $meminfo = @file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+)\s*kB/',      $meminfo, $mt);
        preg_match('/MemAvailable:\s+(\d+)\s*kB/',  $meminfo, $ma);

        $totalKb = isset($mt[1]) ? (int)$mt[1] : 0;
        $availKb = isset($ma[1]) ? (int)$ma[1] : 0;
        $usedKb = max(0, $totalKb - $availKb);
        $pct = $totalKb > 0 ? round($usedKb / $totalKb * 100) : 0;

        return [
            'cpu_load' => [
                '1min' => $loads[0],
                '5min' => $loads[1],
                '15min' => $loads[2],
            ],
            'ram' => [
                'total' => $totalKb * 1024,
                'used' => $usedKb * 1024,
                'available' => $availKb * 1024,
                'percent' => $pct,
            ],
        ];
    }

    /**
     * Format bytes to human-readable format
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get required PHP extensions
     */
    public static function getRequiredExtensions(): array
    {
        $requiredExtensions = [
            'pdo' => ['required' => true,  'description' => 'PHP Data Objects for database connectivity'],
            'pdo_mysql' => ['required' => true,  'description' => 'PDO driver for MySQL databases'],
            'mbstring' => ['required' => true,  'description' => 'Multibyte string support'],
            'json' => ['required' => true,  'description' => 'JSON support'],
            'openssl' => ['required' => true,  'description' => 'Secure communications and cryptography'],
            'curl' => ['required' => true,  'description' => 'Client URL Library for API calls'],
            'fileinfo' => ['required' => true,  'description' => 'File information detection'],
            'ctype' => ['required' => true,  'description' => 'Character type checking functions'],
            'tokenizer' => ['required' => true,  'description' => 'PHP code tokenizer'],

            'zip' => ['required' => false, 'description' => 'ZIP archive handling'],
            'gd' => ['required' => false, 'description' => 'Graphics library for image processing'],
            'intl' => ['required' => false, 'description' => 'Internationalization functions'],
            'simplexml' => ['required' => false, 'description' => 'SimpleXML support for XML parsing'],
            'xml' => ['required' => false, 'description' => 'Core XML parsing support'],
            'session' => ['required' => false, 'description' => 'Session management'],
            'bcmath' => ['required' => false, 'description' => 'Arbitrary precision mathematics library'],
            'gmp' => ['required' => false, 'description' => 'GNU Multiple Precision support for big integers'],
            'opcache' => ['required' => false, 'description' => 'Improves PHP performance by storing precompiled script bytecode'],
        ];

        $loadedExtensions = get_loaded_extensions();
        $result = [];

        foreach ($requiredExtensions as $extension => $info) {
            if ($extension === 'opcache') {
                $isLoaded = extension_loaded('Zend OPcache') || extension_loaded('opcache');
            } else {
                $isLoaded = in_array(strtolower($extension), array_map('strtolower', $loadedExtensions));
            }

            $result[$extension] = [
                'loaded' => $isLoaded,
                'required' => $info['required'],
                'description' => $info['description'],
            ];
        }

        return $result;
    }

    /**
     * Check PHP version
     */
    public static function checkPhpVersion(): bool
    {
        return version_compare(PHP_VERSION, '8.0.0', '>=');
    }

    /**
     * Get PHP setting warnings
     */
    public static function getPhpSettingWarnings(): array
    {
        $warnings = [];

        if (!self::checkPhpVersion()) {
            $warnings['version'] = 'PHP version 8.0.0 or higher is recommended for optimal performance';
        }

        $memoryLimit = ini_get('memory_limit');
        $memoryLimitInt = (int)$memoryLimit;
        if ($memoryLimitInt < 128 && $memoryLimit !== '-1') {
            $warnings['memory_limit'] = 'Memory limit below 128M may cause performance issues';
        }

        $maxExecutionTime = (int)ini_get('max_execution_time');
        if ($maxExecutionTime < 30 && $maxExecutionTime !== 0) {
            $warnings['max_execution_time'] = 'Low execution time limit may cause timeout issues with complex operations';
        }

        $uploadMaxFilesize = (int)ini_get('upload_max_filesize');
        if ($uploadMaxFilesize < 8) {
            $warnings['upload_max_filesize'] = 'Low upload filesize limit may restrict file upload capabilities';
        }

        $postMaxSize = (int)ini_get('post_max_size');
        if ($postMaxSize < 8) {
            $warnings['post_max_size'] = 'Low post size limit may restrict form submission capabilities';
        }

        if (extension_loaded('Zend OPcache') || extension_loaded('opcache')) {
            if (function_exists('opcache_get_status')) {
                $opcacheStatus = @opcache_get_status(false);
                if ($opcacheStatus === false || !$opcacheStatus['opcache_enabled']) {
                    $warnings['opcache'] = 'OPCache is disabled. Enabling it can significantly improve performance';
                }
            } else {
                $warnings['opcache'] = 'OPCache extension is available but not properly configured';
            }
        } else {
            $warnings['opcache'] = 'OPCache extension is not available. Installing it can significantly improve performance';
        }

        if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
            if (extension_loaded('Zend OPcache') || extension_loaded('opcache')) {
                $jitBuffer = 0;
                if (function_exists('opcache_get_status')) {
                    $status = @opcache_get_status(false);
                    if ($status !== false && isset($status['jit']['buffer_size'])) {
                        $jitBuffer = $status['jit']['buffer_size'];
                    }
                }
                if ($jitBuffer === 0) {
                    $jitBuffer = (int) ini_get('opcache.jit_buffer_size');
                }
                if ($jitBuffer === 0) {
                    $warnings['jit'] = 'JIT is disabled. Enabling it can improve performance for computation-heavy tasks';
                }
            } else {
                $warnings['jit'] = 'JIT requires OPCache extension which is not available';
            }
        }

        return $warnings;
    }

    /**
     * Get system health information
     */
    public static function getSystemHealth(): array
    {
        return [
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'limit' => self::convertToBytes(ini_get('memory_limit')),
                'formatted' => self::formatBytes(memory_get_usage(true)) . ' / ' . ini_get('memory_limit'),
                'percentage' => min(100, round((memory_get_usage(true) / self::convertToBytes(ini_get('memory_limit'))) * 100)),
            ],
            'disk_usage' => [
                'total' => disk_total_space(__DIR__),
                'free' => disk_free_space(__DIR__),
                'used' => disk_total_space(__DIR__) - disk_free_space(__DIR__),
                'formatted' => self::formatBytes(disk_total_space(__DIR__) - disk_free_space(__DIR__)) . ' / ' . self::formatBytes(disk_total_space(__DIR__)),
                'percentage' => round(((disk_total_space(__DIR__) - disk_free_space(__DIR__)) / disk_total_space(__DIR__)) * 100),
            ],
        ];
    }

    /**
     * Detect database driver from config
     */
    private static function detectDatabaseDriver(): string
    {
        $dbConfig = config('database.connections.default');
        $dbType = explode('\\', $dbConfig->driver);

        return str_replace('Driver', '', end($dbType));
    }

    /**
     * Convert PHP ini value to bytes
     */
    private static function convertToBytes(string $val): int
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $val = (int)$val;

        switch ($last) {
            case 'g':
                $val *= 1024;
                // no break
            case 'm':
                $val *= 1024;
                // no break
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
}
