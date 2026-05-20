<?php

namespace Flute\Admin\Packages\AboutSystem\Controllers\Concerns;

use Throwable;

trait GeneratesRequestInfo
{
    protected function generateSessionSection(): string
    {
        $lines = [
            $this->sectionTitle('SESSION CONFIGURATION'),
        ];

        try {
            $lines[] = $this->formatKeyValue('Session Handler', ini_get('session.save_handler'));
            $lines[] = $this->formatKeyValue('Session Path', '***');
            $lines[] = $this->formatKeyValue('Session Name', ini_get('session.name'));
            $lines[] = $this->formatKeyValue('Session Lifetime', ini_get('session.gc_maxlifetime') . 's');
            $lines[] = $this->formatKeyValue('Cookie Lifetime', ini_get('session.cookie_lifetime') . 's');
            $lines[] = $this->formatKeyValue('Cookie Secure', ini_get('session.cookie_secure') ? 'Yes' : 'No');
            $lines[] = $this->formatKeyValue('Cookie HttpOnly', ini_get('session.cookie_httponly') ? 'Yes' : 'No');
            $lines[] = $this->formatKeyValue('Cookie SameSite', ini_get('session.cookie_samesite') ?: 'Not set');
            $lines[] = $this->formatKeyValue('Use Strict Mode', ini_get('session.use_strict_mode') ? 'Yes' : 'No');
        } catch (Throwable $e) {
            $lines[] = 'Error getting session info: ' . $this->sanitizeErrorMessage($e->getMessage());
        }

        return implode("\n", $lines);
    }

    protected function generateRequestSection(): string
    {
        $lines = [
            $this->sectionTitle('CURRENT REQUEST INFO'),
        ];

        try {
            $request = request();

            $lines[] = $this->formatKeyValue('Method', $request->getMethod());
            $lines[] = $this->formatKeyValue('URI', $request->getRequestUri());
            $lines[] = $this->formatKeyValue('Host', '***');
            $lines[] = $this->formatKeyValue('Scheme', $request->getScheme());
            $lines[] = $this->formatKeyValue('Is Secure', $request->isSecure() ? 'Yes' : 'No');
            $lines[] = $this->formatKeyValue('Client IP', '***');
            $lines[] = $this->formatKeyValue('User Agent', substr($request->headers->get('User-Agent', 'N/A'), 0, 80));

            $lines[] = '';
            $lines[] = 'Request Headers:';
            foreach ($request->headers->all() as $name => $values) {
                $value = implode(', ', $values);
                if ($this->isSensitiveKey($name)) {
                    $value = '***';
                }
                $lines[] = sprintf('  %-25s: %s', $name, substr($value, 0, 100));
            }

            $lines[] = '';
            $lines[] = 'Server Variables (selected):';
            $sensitiveServerVars = ['REMOTE_ADDR', 'REMOTE_PORT'];
            $serverVars = [
                'REQUEST_TIME',
                'REQUEST_TIME_FLOAT',
                'REMOTE_ADDR',
                'REMOTE_PORT',
                'SERVER_PROTOCOL',
                'GATEWAY_INTERFACE',
                'HTTPS',
            ];
            foreach ($serverVars as $var) {
                if (isset($_SERVER[$var])) {
                    $val = in_array($var, $sensitiveServerVars, true) ? '***' : $_SERVER[$var];
                    $lines[] = sprintf('  %-25s: %s', $var, $val);
                }
            }
        } catch (Throwable $e) {
            $lines[] = 'Error getting request info: ' . $this->sanitizeErrorMessage($e->getMessage());
        }

        return implode("\n", $lines);
    }

    protected function generateFullLogsSection(): string
    {
        $lines = [
            $this->sectionTitle('FULL LOG FILES'),
        ];

        try {
            $logFiles = $this->logViewerService->getLogFiles();

            if (empty($logFiles)) {
                $lines[] = 'No log files found.';

                return implode("\n", $lines);
            }

            foreach ($logFiles as $fileName => $fileInfo) {
                $lines[] = '';
                $lines[] =
                    '--- '
                    . $fileName
                    . ' ('
                    . ( $fileInfo['size'] ?? '?' )
                    . ', '
                    . ( $fileInfo['modified'] ?? '?' )
                    . ') ---';

                $logPath = path('storage/logs/' . $fileName);
                if (file_exists($logPath)) {
                    $content = file_get_contents($logPath);

                    $maxSize = 50 * 1024;
                    if (strlen($content) > $maxSize) {
                        $content = "[TRUNCATED - last 50KB]\n" . substr($content, -$maxSize);
                    }

                    $lines[] = $this->sanitizeLogContent($content);
                }
            }
        } catch (Throwable $e) {
            $lines[] = 'Error loading logs: ' . $this->sanitizeErrorMessage($e->getMessage());
        }

        return implode("\n", $lines);
    }
}
