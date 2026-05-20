<?php

namespace Flute\Admin\Packages\AboutSystem\Controllers\Concerns;

use Flute\Admin\Packages\AboutSystem\Helpers\AboutSystemHelper;
use Flute\Core\App;
use Throwable;

trait GeneratesEnvironmentInfo
{
    protected function generateSystemSection(): string
    {
        $systemInfo = AboutSystemHelper::getSystemInfo();
        $resourceUsage = AboutSystemHelper::getResourceUsage();

        $lines = [
            $this->sectionTitle('FLUTE CMS INFORMATION'),
            $this->formatKeyValue('Version', App::VERSION),
            $this->formatKeyValue('Framework', $systemInfo['framework'] ?? 'Flute CMS'),
            $this->formatKeyValue('License', $systemInfo['license'] ?? 'N/A'),
            $this->formatKeyValue('Project URL', $systemInfo['project_link'] ?? 'N/A'),
            '',
            $this->formatKeyValue('Debug Mode', config('app.debug', false) ? 'Enabled' : 'Disabled'),
            $this->formatKeyValue('Environment', config('app.env', 'production')),
            $this->formatKeyValue('Performance Mode', is_performance() ? 'Enabled' : 'Disabled'),
            $this->formatKeyValue('Base URL', '***'),
            $this->formatKeyValue('Timezone', config('app.timezone', date_default_timezone_get())),
            $this->formatKeyValue('Locale', config('app.locale', 'en')),
            '',
            $this->formatKeyValue('CPU Load (1/5/15 min)', sprintf(
                '%.2f / %.2f / %.2f',
                $resourceUsage['cpu_load']['1min'],
                $resourceUsage['cpu_load']['5min'],
                $resourceUsage['cpu_load']['15min'],
            )),
            $this->formatKeyValue('RAM Usage', sprintf(
                '%s / %s (%d%%)',
                AboutSystemHelper::formatBytes($resourceUsage['ram']['used']),
                AboutSystemHelper::formatBytes($resourceUsage['ram']['total']),
                $resourceUsage['ram']['percent'],
            )),
        ];

        try {
            $bootTimes = app()->getBootTimes();
            if (!empty($bootTimes)) {
                $lines[] = '';
                $lines[] = 'Service Provider Boot Times:';
                arsort($bootTimes);
                $top10 = array_slice($bootTimes, 0, 10, true);
                foreach ($top10 as $provider => $time) {
                    $shortName = substr(strrchr($provider, '\\') ?: $provider, 1);
                    $lines[] = sprintf('  %-40s %.3fs', $shortName, $time);
                }
                $lines[] = sprintf('  %-40s %.3fs', 'TOTAL', array_sum($bootTimes));
            }
        } catch (Throwable) {
        }

        return implode("\n", $lines);
    }

    protected function generatePhpSection(): string
    {
        $phpInfo = AboutSystemHelper::getPhpInfo();
        $warnings = AboutSystemHelper::getPhpSettingWarnings();

        $lines = [
            $this->sectionTitle('PHP CONFIGURATION'),
            $this->formatKeyValue('PHP Version', PHP_VERSION),
            $this->formatKeyValue('SAPI', php_sapi_name()),
            $this->formatKeyValue('Memory Limit', $phpInfo['memory_limit'] ?? ini_get('memory_limit')),
            $this->formatKeyValue(
                'Max Execution Time',
                $phpInfo['max_execution_time'] ?? ini_get('max_execution_time') . 's',
            ),
            $this->formatKeyValue(
                'Upload Max Filesize',
                $phpInfo['upload_max_filesize'] ?? ini_get('upload_max_filesize'),
            ),
            $this->formatKeyValue('Post Max Size', $phpInfo['post_max_size'] ?? ini_get('post_max_size')),
            $this->formatKeyValue('Max Input Vars', ini_get('max_input_vars')),
            $this->formatKeyValue('Display Errors', ini_get('display_errors') ? 'On' : 'Off'),
            $this->formatKeyValue('Error Reporting', $this->getErrorReportingString()),
            '',
            $this->formatKeyValue('OPcache', $phpInfo['opcache'] ?? 'N/A'),
            $this->formatKeyValue('JIT', $phpInfo['jit'] ?? 'N/A'),
            '',
            $this->formatKeyValue('Current Memory Usage', AboutSystemHelper::formatBytes(memory_get_usage(true))),
            $this->formatKeyValue('Peak Memory Usage', AboutSystemHelper::formatBytes(memory_get_peak_usage(true))),
        ];

        if (!empty($warnings)) {
            $lines[] = '';
            $lines[] = 'PHP Warnings:';
            foreach ($warnings as $key => $warning) {
                $lines[] = '  [!] ' . $warning;
            }
        }

        return implode("\n", $lines);
    }

    protected function generateServerSection(): string
    {
        $serverInfo = AboutSystemHelper::getServerInfo();

        $lines = [
            $this->sectionTitle('SERVER INFORMATION'),
            $this->formatKeyValue('Operating System', PHP_OS . ' (' . php_uname('r') . ')'),
            $this->formatKeyValue(
                'Server Software',
                $serverInfo['server_software'] ?? $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            ),
            $this->formatKeyValue(
                'Server Protocol',
                $serverInfo['server_protocol'] ?? $_SERVER['SERVER_PROTOCOL'] ?? 'N/A',
            ),
            $this->formatKeyValue('Server Name', '***'),
            $this->formatKeyValue('Server Port', $serverInfo['server_port'] ?? $_SERVER['SERVER_PORT'] ?? 'N/A'),
            $this->formatKeyValue('Document Root', '***'),
            $this->formatKeyValue('Server IP', '***'),
            '',
            $this->formatKeyValue('Disk Total', $serverInfo['disk_total_space'] ?? 'N/A'),
            $this->formatKeyValue('Disk Free', $serverInfo['disk_free_space'] ?? 'N/A'),
            $this->formatKeyValue('Disk Used', $serverInfo['disk_used_space'] ?? 'N/A'),
            $this->formatKeyValue('Disk Usage', $serverInfo['disk_usage_percent'] ?? 'N/A'),
        ];

        return implode("\n", $lines);
    }

    protected function generateExtensionsSection(): string
    {
        $extensions = AboutSystemHelper::getRequiredExtensions();
        $loadedExtensions = get_loaded_extensions();

        $lines = [
            $this->sectionTitle('PHP EXTENSIONS'),
            '',
            'Required Extensions:',
        ];

        foreach ($extensions as $name => $info) {
            $status = $info['loaded'] ? '[OK]' : ( $info['required'] ? '[MISSING]' : '[NOT LOADED]' );
            $required = $info['required'] ? ' (required)' : ' (optional)';
            $lines[] = sprintf('  %s %s%s', $status, $name, $required);
        }

        $lines[] = '';
        $lines[] = 'All Loaded Extensions (' . count($loadedExtensions) . '):';
        $lines[] = '  ' . wordwrap(implode(', ', $loadedExtensions), 70, "\n  ");

        return implode("\n", $lines);
    }
}
