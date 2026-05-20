<?php

namespace Flute\Admin\Packages\AboutSystem\Controllers\Concerns;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

trait ReportFormatting
{
    protected function formatBytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(( $bytes ? log($bytes) : 0 ) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= 1 << ( 10 * $pow );

        return round($bytes, 1) . ' ' . $units[$pow];
    }

    protected function calculateMedian(array $values): float
    {
        if (empty($values)) {
            return 0;
        }

        sort($values);
        $count = count($values);
        $middle = (int) floor($count / 2);

        if (( $count % 2 ) === 0) {
            return ( $values[$middle - 1] + $values[$middle] ) / 2;
        }

        return $values[$middle];
    }

    protected function calculateStdDev(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(static fn($v) => pow($v - $mean, 2), $values);

        return sqrt(array_sum($squaredDiffs) / count($values));
    }

    protected function sectionTitle(string $title): string
    {
        return "\n=== " . $title . ' ===';
    }

    protected function formatKeyValue(string $key, string $value): string
    {
        return sprintf('%-25s: %s', $key, $value);
    }

    protected function getErrorReportingString(): string
    {
        $level = error_reporting();
        $flags = [];

        if ($level & E_ERROR) {
            $flags[] = 'E_ERROR';
        }
        if ($level & E_WARNING) {
            $flags[] = 'E_WARNING';
        }
        if ($level & E_PARSE) {
            $flags[] = 'E_PARSE';
        }
        if ($level & E_NOTICE) {
            $flags[] = 'E_NOTICE';
        }
        if ($level & E_STRICT) {
            $flags[] = 'E_STRICT';
        }
        if ($level & E_DEPRECATED) {
            $flags[] = 'E_DEPRECATED';
        }

        if ($level === E_ALL) {
            return 'E_ALL';
        }

        return implode(' | ', array_slice($flags, 0, 4)) . ( count($flags) > 4 ? '...' : '' );
    }

    protected function isSensitiveKey(string $key): bool
    {
        $sensitivePatterns = [
            'key',
            'secret',
            'password',
            'token',
            'api',
            'steam_api',
            'cookie',
            'authorization',
            'auth',
            'session',
            'csrf',
        ];

        $sensitiveExact = [
            'app.url',
            'app.name',
            'mail.host',
            'mail.port',
            'host',
            'x-forwarded-for',
            'x-real-ip',
            'referer',
            'x-forwarded-host',
            'x-forwarded-proto',
        ];

        $lowerKey = strtolower($key);

        if (in_array($lowerKey, $sensitiveExact, true)) {
            return true;
        }

        foreach ($sensitivePatterns as $pattern) {
            if (stripos($key, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function sanitizeErrorMessage(string $message): string
    {
        $basePath = str_replace('\\', '/', rtrim((string) BASE_PATH, '/\\'));
        $message = str_replace([$basePath, str_replace('/', '\\', $basePath)], '[PROJECT_ROOT]', $message);

        $message = (string) preg_replace('#(?:/[a-zA-Z][a-zA-Z0-9._-]*/){3,}#', '[***]/', $message);
        $message = (string) preg_replace('#(?:[A-Z]:\\\\(?:[^\\\\]+\\\\){2,})#i', '[***]\\', $message);
        $message = (string) preg_replace('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', '*.*.*.*', $message);

        return $message;
    }

    protected function sanitizeSqlQuery(string $query): string
    {
        $query = (string) preg_replace("/'.+?'/", "'***'", $query);
        $query = (string) preg_replace('/"[^"]+?"/', '"***"', $query);
        $query = (string) preg_replace('/\b\d+\b/', '?', $query);

        return $query;
    }

    protected function sanitizeLogContent(string $content): string
    {
        $content = (string) preg_replace('/password["\']?\s*[:=]\s*["\']?[^"\'\s,}\n]+/i', 'password=***', $content);
        $content = (string) preg_replace('/token["\']?\s*[:=]\s*["\']?[^"\'\s,}\n]+/i', 'token=***', $content);
        $content = (string) preg_replace('/(\?|&)accessKey=([^&\s\n]+)/i', '$1accessKey=***', $content);
        $content = (string) preg_replace('/api[_-]?key["\']?\s*[:=]\s*["\']?[^"\'\s,}\n]+/i', 'api_key=***', $content);
        $content = (string) preg_replace('/secret["\']?\s*[:=]\s*["\']?[^"\'\s,}\n]+/i', 'secret=***', $content);
        $content = (string) preg_replace(
            '/Authorization:\s*Bearer\s+[^\s\n]+/i',
            'Authorization: Bearer ***',
            $content,
        );

        $content = (string) preg_replace('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', '*.*.*.*', $content);
        $content = (string) preg_replace('/cookie["\']?\s*[:=]\s*["\']?[^"\'\s,}\n]+/i', 'cookie=***', $content);

        $basePath = str_replace('\\', '/', rtrim((string) BASE_PATH, '/\\'));
        $content = str_replace([$basePath, str_replace('/', '\\', $basePath)], '[PROJECT_ROOT]', $content);

        $content = (string) preg_replace(
            '#(?:/[a-zA-Z][a-zA-Z0-9._-]*/){3,}[a-zA-Z0-9._-]+\.php#',
            '[***].php',
            $content,
        );
        $content = (string) preg_replace('#(?:[A-Z]:\\\\(?:[^\\\\]+\\\\){2,})[^\\\\]+\.php#i', '[***].php', $content);

        return $content;
    }

    protected function getDirectorySize(string $path): int
    {
        $size = 0;

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY,
            );

            $count = 0;
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                    $count++;
                    if ($count > 10000) {
                        break;
                    }
                }
            }
        } catch (Throwable) {
        }

        return $size;
    }
}
