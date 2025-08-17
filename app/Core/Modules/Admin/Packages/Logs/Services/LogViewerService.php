<?php

namespace Flute\Admin\Packages\Logs\Services;

use FilesystemIterator;
use Flute\Core\Services\LoggerService;

class LogViewerService
{
    protected LoggerService $loggerService;
    protected array $logCache = [];
    protected int $cacheLifetime = 60;

    public function __construct(LoggerService $loggerService)
    {
        $this->loggerService = $loggerService;
    }

    /**
     * Get list of available loggers
     */
    public function getLoggersList(): array
    {
        return $this->loggerService->getLoggersNames();
    }

    /**
     * Get log file content with caching
     */
    public function getLogContent(string $logFile, int $lines = 500): array
    {
        $cacheKey = $this->getCacheKey($logFile, $lines);

        if ($this->isCacheValid($cacheKey)) {
            return $this->logCache[$cacheKey]['data'];
        }

        $logPath = path('storage/logs/' . $logFile);

        if (!file_exists($logPath)) {
            return [];
        }

        $content = $this->readLastLines($logPath, $lines);
        $parsedEntries = $this->parseLogContent($content);

        usort($parsedEntries, function ($a, $b) {
            return strtotime($b['datetime']) <=> strtotime($a['datetime']);
        });

        $this->cacheLogData($cacheKey, $parsedEntries);

        return $parsedEntries;
    }

    /**
     * Read last lines from file
     */
    protected function readLastLines(string $filePath, int $lines): string
    {
        if (!file_exists($filePath)) {
            return '';
        }

        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        if ($totalLines <= $lines) {
            return file_get_contents($filePath);
        }

        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);

        $content = '';
        while (!$file->eof()) {
            $content .= $file->fgets();
        }

        return $content;
    }

    /**
     * Get file context around error line (code context)
     */
    public function getFileContext(string $filePath, int $errorLine, int $contextLines = 20): array
    {
        if (!$this->isAllowedFile($filePath)) {
            return [];
        }

        if (!file_exists($filePath)) {
            return [];
        }

        try {
            $lines = file($filePath, FILE_IGNORE_NEW_LINES);
            $totalLines = count($lines);

            if ($errorLine <= 0 || $errorLine > $totalLines) {
                return [];
            }

            $startLine = max(1, $errorLine - $contextLines);
            $endLine = min($totalLines, $errorLine + $contextLines);

            $context = [];
            for ($i = $startLine; $i <= $endLine; $i++) {
                $context[] = [
                    'line_number' => $i,
                    'content' => $lines[$i - 1] ?? '',
                    'is_error_line' => $i === $errorLine,
                ];
            }

            return $context;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if file is allowed to be read
     */
    protected function isAllowedFile(string $filePath): bool
    {
        $realPath = realpath($filePath);
        $basePath = realpath(BASE_PATH);

        if (!$realPath || !$basePath || strpos($realPath, $basePath) !== 0) {
            return false;
        }

        if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'php') {
            return false;
        }

        $excludePatterns = [
            '/config/',
            '/.env',
            '/node_modules/',
        ];

        foreach ($excludePatterns as $pattern) {
            if (strpos($realPath, $pattern) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract file path and line number from error message
     */
    public function extractFileInfo(string $message): array
    {
        $filePath = '';
        $lineNumber = 0;

        $patterns = [
            '/in\s+(.+\.php)\s+on\s+line\s+(\d+)/',
            '/(.+\.php):(\d+)/',
            '/(.+\.php)\((\d+)\)/',
            '/Stack trace:\s*#0\s+(.+\.php)\((\d+)\)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $filePath = $matches[1] ?? '';
                $lineNumber = (int)($matches[2] ?? 0);

                break;
            }
        }

        return [
            'file_path' => $filePath,
            'line_number' => $lineNumber,
            'file_name' => $filePath ? basename($filePath) : '',
            'relative_path' => $filePath ? $this->getRelativePath($filePath) : '',
        ];
    }

    /**
     * Get relative path from project root
     */
    protected function getRelativePath(string $filePath): string
    {
        $basePath = realpath(BASE_PATH);
        $realPath = realpath($filePath);

        if ($basePath && $realPath && strpos($realPath, $basePath) === 0) {
            return ltrim(substr($realPath, strlen($basePath)), '/\\');
        }

        return $filePath;
    }

    /**
     * Clear log file
     */
    public function clearLog(string $logger): bool
    {
        $logPath = path('storage/logs/' . $logger);
        if (!file_exists($logPath)) {
            return false;
        }

        $this->clearLogCache($logger);

        return file_put_contents($logPath, '') !== false;
    }

    /**
     * Parse log content
     */
    protected function parseLogContent(string $content): array
    {
        if (empty($content)) {
            return [];
        }

        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*?)(?=\n\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]|$)/s';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        $entries = [];
        foreach ($matches as $match) {
            if (count($match) < 5) {
                continue;
            }

            $datetime = $match[1] ?? '';
            $channel = $match[2] ?? 'unknown';
            $level = strtolower($match[3] ?? 'info');
            $message = $match[4] ?? '';

            $messageParts = $this->parseMessageAndContext($message);

            $cleanMessage = $this->sanitizeMessage($messageParts['message']);

            $fileInfo = $this->extractFileInfo($message);

            if (empty($fileInfo['file_path']) && !empty($messageParts['context'])) {
                $decodedContext = json_decode($messageParts['context'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedContext)) {
                    $ctxFile = $decodedContext['file'] ?? '';
                    $ctxLine = (int)($decodedContext['line'] ?? 0);
                    if ($ctxFile && $ctxLine) {
                        $fileInfo = [
                            'file_path' => $ctxFile,
                            'line_number' => $ctxLine,
                            'file_name' => basename($ctxFile),
                            'relative_path' => $this->getRelativePath($ctxFile),
                        ];
                    }
                }
            }

            $entries[] = [
                'datetime' => $datetime,
                'channel' => $channel,
                'level' => $level,
                'message' => $cleanMessage,
                'context' => $messageParts['context'],
                'full_message' => trim($message),
                'severity' => $this->getLevelSeverity($level),
                'file_info' => $fileInfo,
                'code_context' => $fileInfo['file_path'] && $fileInfo['line_number']
                    ? $this->getFileContext($fileInfo['file_path'], $fileInfo['line_number'], 20)
                    : [],
            ];
        }

        return $entries;
    }

    /**
     * Parse message and context from log entry
     */
    protected function parseMessageAndContext(string $message): array
    {
        $contextStart = strrpos($message, ' {');

        if ($contextStart !== false) {
            $messageText = trim(substr($message, 0, $contextStart));
            $contextJson = trim(substr($message, $contextStart));

            $context = json_decode($contextJson, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return [
                    'message' => $messageText,
                    'context' => $contextJson,
                ];
            }
        }

        return [
            'message' => trim($message),
            'context' => '',
        ];
    }

    /**
     * Sanitize message for display
     */
    protected function sanitizeMessage(string $message): string
    {
        $message = preg_replace('/(\?|&)accessKey=([^&\s]+)/i', '$1accessKey=***', $message);
        $message = preg_replace('/password["\']?\s*[:=]\s*["\']?([^"\'\s,}]+)/i', 'password="***"', $message);
        $message = preg_replace('/token["\']?\s*[:=]\s*["\']?([^"\'\s,}]+)/i', 'token="***"', $message);

        $message = str_replace("\n", "<br>", $message);

        return $message;
    }

    /**
     * Get severity level for sorting
     */
    protected function getLevelSeverity(string $level): int
    {
        $severityMap = [
            'emergency' => 8,
            'alert' => 7,
            'critical' => 6,
            'error' => 5,
            'warning' => 4,
            'notice' => 3,
            'info' => 2,
            'debug' => 1,
        ];

        return $severityMap[$level] ?? 2;
    }

    /**
     * Get list of available log files with enhanced metadata
     */
    public function getLogFiles(): array
    {
        $result = [];
        $logsPath = path('storage/logs');

        if (!is_dir($logsPath)) {
            return $result;
        }

        $fs = new FilesystemIterator($logsPath);

        foreach ($fs as $file) {
            if ($file->isFile() && $file->getExtension() === 'log') {
                $size = $file->getSize();
                $modified = $file->getMTime();
                $fileName = $file->getFilename();

                $levelStats = $this->getLogLevelStats($fileName);

                $result[$fileName] = [
                    'name' => $fileName,
                    'path' => $file->getPathname(),
                    'size' => $this->formatBytes($size),
                    'size_bytes' => $size,
                    'modified' => date(default_date_format(), $modified),
                    'modified_timestamp' => $modified,
                    'level_stats' => $levelStats,
                    'is_active' => $this->isActiveLogFile($fileName),
                ];
            }
        }

        uasort($result, function ($a, $b) {
            return $b['modified_timestamp'] <=> $a['modified_timestamp'];
        });

        return $result;
    }

    /**
     * Get log level statistics for a file
     */
    protected function getLogLevelStats(string $logFile): array
    {
        $cacheKey = 'stats_' . $logFile;

        if ($this->isCacheValid($cacheKey)) {
            return $this->logCache[$cacheKey]['data'];
        }

        $entries = $this->getLogContent($logFile, 200);
        $stats = [];

        foreach ($entries as $entry) {
            $level = $entry['level'];
            if (!isset($stats[$level])) {
                $stats[$level] = 0;
            }
            $stats[$level]++;
        }

        $this->cacheLogData($cacheKey, $stats);

        return $stats;
    }

    /**
     * Check if log file is currently active
     */
    protected function isActiveLogFile(string $fileName): bool
    {
        $logPath = path('storage/logs/' . $fileName);
        if (!file_exists($logPath)) {
            return false;
        }

        $lastModified = filemtime($logPath);

        return (time() - $lastModified) < 3600;
    }

    /**
     * Format file size to readable view
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Export logs with system information (for debugging)
     */
    public function exportLogs(string $logFile): string
    {
        $logPath = path('storage/logs/' . $logFile);

        if (!file_exists($logPath)) {
            throw new \Exception('Log file not found');
        }

        $content = file_get_contents($logPath);
        $systemInfo = $this->getSystemInfo();

        $exportContent = "# System Information\n";
        $exportContent .= "Export Date: " . date('Y-m-d H:i:s') . "\n";
        $exportContent .= "Log File: " . $logFile . "\n\n";

        foreach ($systemInfo as $section => $data) {
            $exportContent .= "## " . $section . "\n";
            foreach ($data as $key => $value) {
                $exportContent .= $key . ": " . $value . "\n";
            }
            $exportContent .= "\n";
        }

        $exportContent .= "# Log Content\n\n";
        $exportContent .= $content;

        $tempDir = path('storage/app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0o755, true);
        }

        $exportFileName = 'log_export_' . date('Y-m-d_H-i-s') . '_' . pathinfo($logFile, PATHINFO_FILENAME) . '.txt';
        $exportFilePath = $tempDir . '/' . $exportFileName;

        if (file_put_contents($exportFilePath, $exportContent) === false) {
            throw new \Exception('Failed to create export file');
        }

        return $exportFilePath;
    }

    /**
     * Get comprehensive system information
     */
    protected function getSystemInfo(): array
    {
        $diskInfo = $this->getDiskInfo();
        $memoryInfo = $this->getMemoryInfo();
        $phpInfo = $this->getPhpInfo();
        $fluteInfo = $this->getFluteInfo();

        return [
            'Server' => [
                'PHP Version' => PHP_VERSION,
                'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'Operating System' => PHP_OS,
                'Hostname' => gethostname(),
                'Server IP' => $_SERVER['SERVER_ADDR'] ?? ($_SERVER['LOCAL_ADDR'] ?? 'Unknown'),
                'Server Time' => date('Y-m-d H:i:s'),
                'Timezone' => date_default_timezone_get(),
                'Server Load' => $this->getServerLoad(),
            ],
            'Memory & Performance' => $memoryInfo,
            'Disk Space' => $diskInfo,
            'PHP Configuration' => $phpInfo,
            'Flute Framework' => $fluteInfo,
        ];
    }

    /**
     * Get disk information
     */
    protected function getDiskInfo(): array
    {
        $diskInfo = [];

        if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
            $diskFree = disk_free_space('/');
            $diskTotal = disk_total_space('/');

            if ($diskFree !== false && $diskTotal !== false) {
                $diskUsed = $diskTotal - $diskFree;
                $diskInfo = [
                    'Total Space' => $this->formatBytes($diskTotal),
                    'Free Space' => $this->formatBytes($diskFree),
                    'Used Space' => $this->formatBytes($diskUsed),
                    'Usage Percentage' => round(($diskUsed / $diskTotal) * 100, 2) . '%',
                ];
            }
        }

        return $diskInfo ?: ['Status' => 'Disk information not available'];
    }

    /**
     * Get memory information
     */
    protected function getMemoryInfo(): array
    {
        return [
            'Memory Limit' => ini_get('memory_limit'),
            'Max Execution Time' => ini_get('max_execution_time') . ' seconds',
            'Upload Max Filesize' => ini_get('upload_max_filesize'),
            'Post Max Size' => ini_get('post_max_size'),
            'Current Memory Usage' => $this->formatBytes(memory_get_usage(true)),
            'Peak Memory Usage' => $this->formatBytes(memory_get_peak_usage(true)),
        ];
    }

    /**
     * Get PHP information
     */
    protected function getPhpInfo(): array
    {
        return [
            'PHP Version' => phpversion(),
            'SAPI Mode' => php_sapi_name(),
            'OPcache Status' => extension_loaded('Zend OPcache') ? 'Enabled' : 'Disabled',
            'Extensions Count' => count(get_loaded_extensions()),
            'Key Extensions' => $this->getKeyExtensions(),
        ];
    }

    /**
     * Get Flute framework information
     */
    protected function getFluteInfo(): array
    {
        return [
            'Environment' => getenv('APP_ENV') ?: config('app.env', 'production'),
            'Debug Mode' => config('app.debug', false) ? 'Enabled' : 'Disabled',
            'Database Driver' => config('database.default', 'mysql'),
            'Timezone' => config('app.timezone', 'UTC'),
            'Cache Driver' => config('cache.default', 'file'),
        ];
    }

    /**
     * Get server load if available
     */
    protected function getServerLoad(): string
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();

            return sprintf('%.2f %.2f %.2f', $load[0], $load[1], $load[2]);
        }

        return 'Not available';
    }

    /**
     * Get key PHP extensions
     */
    protected function getKeyExtensions(): string
    {
        $keyExtensions = ['curl', 'gd', 'mbstring', 'openssl', 'pdo_mysql', 'zip', 'json', 'bcmath'];
        $loaded = [];

        foreach ($keyExtensions as $ext) {
            if (extension_loaded($ext)) {
                $loaded[] = $ext;
            }
        }

        return implode(', ', $loaded);
    }

    /**
     * Generate cache key
     */
    protected function getCacheKey(string $logFile, int $lines): string
    {
        $logPath = path('storage/logs/' . $logFile);
        $lastModified = file_exists($logPath) ? filemtime($logPath) : 0;

        return md5($logFile . '_' . $lines . '_' . $lastModified);
    }

    /**
     * Check if cache is valid
     */
    protected function isCacheValid(string $cacheKey): bool
    {
        if (!isset($this->logCache[$cacheKey])) {
            return false;
        }

        $cacheData = $this->logCache[$cacheKey];

        return (time() - $cacheData['timestamp']) < $this->cacheLifetime;
    }

    /**
     * Cache log data
     */
    protected function cacheLogData(string $cacheKey, array $data): void
    {
        $this->logCache[$cacheKey] = [
            'data' => $data,
            'timestamp' => time(),
        ];

        if (count($this->logCache) > 50) {
            $this->cleanupCache();
        }
    }

    /**
     * Clear cache for specific log file
     */
    protected function clearLogCache(string $logFile): void
    {
        foreach ($this->logCache as $key => $data) {
            if (strpos($key, $logFile) !== false) {
                unset($this->logCache[$key]);
            }
        }
    }

    /**
     * Cleanup old cache entries
     */
    protected function cleanupCache(): void
    {
        $now = time();

        foreach ($this->logCache as $key => $data) {
            if (($now - $data['timestamp']) > $this->cacheLifetime) {
                unset($this->logCache[$key]);
            }
        }
    }
}
