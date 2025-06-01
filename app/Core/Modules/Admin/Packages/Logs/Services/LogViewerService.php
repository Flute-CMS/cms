<?php

namespace Flute\Admin\Packages\Logs\Services;

use FilesystemIterator;
use Flute\Core\Services\LoggerService;

class LogViewerService
{
    protected LoggerService $loggerService;

    public function __construct(LoggerService $loggerService)
    {
        $this->loggerService = $loggerService;
    }

    /**
     * Get list of available loggers
     */
    public function getLoggersList() : array
    {
        return $this->loggerService->getLoggersNames();
    }

    /**
     * Get log file content
     */
    public function getLogContent(string $logFile, int $lines = 500) : array
    {
        $logPath = path('storage/logs/'.$logFile);

        if (! file_exists($logPath)) {
            return [];
        }

        $content = $this->readLastLines($logPath, $lines);
        $parsedEntries = $this->parseLogContent($content);
        
        usort($parsedEntries, function($a, $b) {
            return strtotime($b['datetime']) <=> strtotime($a['datetime']);
        });

        return $parsedEntries;
    }

    /**
     * Read last lines from file
     */
    protected function readLastLines(string $filePath, int $lines) : string
    {
        if (! file_exists($filePath)) {
            return '';
        }

        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);

        $content = '';
        while (! $file->eof()) {
            $content .= $file->fgets();
        }

        return $content;
    }

    /**
     * Clear log file
     */
    public function clearLog(string $logger) : bool
    {
        $logPath = path('storage/logs/'.$logger);
        if (! file_exists($logPath)) {
            return false;
        }
        unlink($logPath);
        return true;
    }

    /**
     * Parse log content
     */
    protected function parseLogContent(string $content) : array
    {
        if (empty($content)) {
            return [];
        }

        $pattern = '/\[(.*?)\] (\w+)\.(\w+): ([\s\S]*?)(?=\n\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]|$)/m';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        $entries = [];
        foreach ($matches as $match) {
            $datetime = $match[1] ?? '';
            $channel = $match[2] ?? '';
            $level = $match[3] ?? '';
            $message = $match[4] ?? '';

            $messageParts = explode(' {', $message, 2);
            $cleanMessage = trim($messageParts[0]);
            $context = isset($messageParts[1]) ? '{' . $messageParts[1] : '';
            
            if (strpos($cleanMessage, "\n") !== false) {
                $cleanMessage = str_replace("\n", "<br>", $cleanMessage);
            }

            $entries[] = [
                'datetime' => $datetime,
                'channel' => $channel,
                'level' => strtolower($level),
                'message' => $cleanMessage,
                'context' => trim($context),
                'full_message' => trim($message),
            ];
        }

        return $entries;
    }

    /**
     * Get list of available log files
     */
    public function getLogFiles() : array
    {
        $result = [];
        $fs = new FilesystemIterator(path('storage/logs'));

        foreach ($fs as $file) {
            if ($file->isFile() && $file->getExtension() === 'log') {
                $size = $file->getSize();
                $modified = $file->getMTime();

                $result[$file->getFilename()] = [
                    'name' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'size' => $this->formatBytes($size),
                    'modified' => date(default_date_format(), $modified),
                    'level' => 200,
                ];
            }
        }

        return $result;
    }

    /**
     * Format file size to readable view
     */
    protected function formatBytes(int $bytes, int $precision = 2) : string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Export logs with system information
     */
    public function exportLogs(string $logFile): string
    {
        $logPath = path('storage/logs/'.$logFile);

        if (! file_exists($logPath)) {
            return '';
        }

        $content = file_get_contents($logPath);
        $systemInfo = $this->getSystemInfo();
        
        $exportContent = "# Системная информация\n";
        $exportContent .= "Дата экспорта: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($systemInfo as $section => $data) {
            $exportContent .= "## " . $section . "\n";
            foreach ($data as $key => $value) {
                $exportContent .= $key . ": " . $value . "\n";
            }
            $exportContent .= "\n";
        }
        
        $exportContent .= "# Содержимое лога\n\n";
        $exportContent .= $content;
        
        $exportFileName = 'log_export_' . date('Y-m-d_H-i-s') . '.txt';
        $exportFilePath = path('storage/app/temp/' . $exportFileName);
        
        file_put_contents($exportFilePath, $exportContent);
        
        return $exportFilePath;
    }
    
    /**
     * Get system information
     */
    protected function getSystemInfo(): array
    {
        $diskInfo = [];
        if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
            $diskFree = disk_free_space('/');
            $diskTotal = disk_total_space('/');
            $diskInfo = [
                'Общий размер диска' => $this->formatBytes($diskTotal),
                'Свободное место' => $this->formatBytes($diskFree),
                'Использовано' => $this->formatBytes($diskTotal - $diskFree),
                'Процент использования' => round(($diskTotal - $diskFree) / $diskTotal * 100, 2) . '%',
            ];
        }
        
        $extensions = [];
        $loadedExtensions = get_loaded_extensions();
        sort($loadedExtensions);
        $extensions = [
            'Загруженные расширения' => implode(', ', array_slice($loadedExtensions, 0, 15)) . '...',
            'Количество расширений' => count($loadedExtensions),
        ];
        
        return [
            'Сервер' => [
                'PHP версия' => PHP_VERSION,
                'Сервер' => $_SERVER['SERVER_SOFTWARE'] ?? 'Неизвестно',
                'ОС' => PHP_OS,
                'Имя хоста' => gethostname(),
                'IP-адрес' => $_SERVER['SERVER_ADDR'] ?? ($_SERVER['LOCAL_ADDR'] ?? 'Неизвестно'),
                'Время сервера' => date('Y-m-d H:i:s'),
                'Временная зона' => date_default_timezone_get(),
            ],
            'Память' => [
                'Лимит памяти' => ini_get('memory_limit'),
                'Максимальное время выполнения' => ini_get('max_execution_time') . ' сек',
                'Загрузка файлов' => ini_get('upload_max_filesize'),
                'Максимальный размер POST' => ini_get('post_max_size'),
                'Текущее использование памяти' => $this->formatBytes(memory_get_usage(true)),
                'Пиковое использование памяти' => $this->formatBytes(memory_get_peak_usage(true)),
            ],
            'Диск' => $diskInfo,
            'PHP' => [
                'Версия PHP' => phpversion(),
                'Режим SAPI' => php_sapi_name(),
                'Модуль OPcache' => extension_loaded('Zend OPcache') ? 'Включен' : 'Выключен',
                'Установленные модули' => implode(', ', [
                    extension_loaded('curl') ? 'CURL' : '',
                    extension_loaded('gd') ? 'GD' : '',
                    extension_loaded('mbstring') ? 'mbstring' : '',
                    extension_loaded('openssl') ? 'OpenSSL' : '',
                    extension_loaded('pdo_mysql') ? 'PDO MySQL' : '',
                    extension_loaded('zip') ? 'ZIP' : '',
                ]),
            ],
            'Flute' => [
                'Окружение' => getenv('APP_ENV') ?: config('app.env', 'production'),
                'Режим отладки' => config('app.debug', false) ? 'Включен' : 'Выключен',
                'База данных' => config('database.default', 'mysql'),
                'Часовой пояс' => config('app.timezone', 'UTC'),
            ],
            'Расширения PHP' => $extensions,
        ];
    }
}