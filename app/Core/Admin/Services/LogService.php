<?php

namespace Flute\Core\Admin\Services;

use Flute\Core\App;
use Flute\Core\Modules\ModuleManager;
use Flute\Core\Theme\ThemeManager;
use Nette\Utils\FileSystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class LogService
{
    public function generateLogFile(): string
    {
        $loggers = config('logging.loggers');

        $logContent = "System Information:\n";
        $logContent .= "Loaded Extensions: " . implode(', ', get_loaded_extensions()) . "\n\n";
        $logContent .= "Requirements Check:\n";
        foreach ($this->reqsForStepTwo() as $key => $value) {
            $logContent .= "{$key}: Required - " . (is_string($value['required']) ? $value['required'] : ($value['required'] ? 'Yes' : 'No')) . ", Current - {$value['current']}\n";
        }
        $logContent .= "\n";

        $logContent .= "Extensions Check:\n";
        $extensionsCheck = $this->extsForStepTwo();
        foreach ($extensionsCheck['list'] as $extension => $info) {
            $logContent .= "{$extension}: " . ucfirst($info['type']) . "\n";
        }
        $logContent .= "Number of Bad Extensions: " . $extensionsCheck['bad'] . "\n\n";

        $logContent .= "\nEngine Information:\n";
        $logContent .= "Flute Version: " . $this->getEngineVersion() . "\n";
        $logContent .= "Performance mode: " . (is_performance() ? 'active' : 'disabled') . "\n";
        $logContent .= "Debug mode: " . (is_debug() ? 'active' : 'disabled') . "\n\n";

        $templates = $this->getTemplates();
        $logContent .= "\nTemplates: \n" . implode('', array_map(function ($template) {
            return "KEY - $template->key | NAME - $template->name | VERSION - $template->version | STATUS - $template->status\n";
        }, $templates)) . "\n";

        $modules = $this->getModules();
        $logContent .= "\nModules: \n" . implode('', array_map(function ($module) {
            return "KEY - $module->key | NAME - $module->name | VERSION - $module->version | STATUS - $module->status\n";
        }, $modules)) . "\n\n";

        $logContent .= "Last 20 Errors from Monolog:\n";

        foreach ($loggers as $loggerName => $loggerConfig) {
            $logContent .= "\nLast 20 Entries for Logger - {$loggerName}:\n";
            $logContent .= $this->getLastLogEntries($loggerConfig['path']) . "\n\n";
        }

        $tempFilePath = BASE_PATH . 'storage/logs/' . now()->format('m-d-y-h-i-s') . '.log';
        FileSystem::write($tempFilePath, $logContent);

        return $tempFilePath;
    }

    public function downloadLogFile(string $filePath): BinaryFileResponse
    {
        return new BinaryFileResponse($filePath, Response::HTTP_OK, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="system_log.txt"'
        ], true, null, false, true);
    }

    protected function reqsForStepTwo()
    {
        $check_results = [];

        $phpVersion = phpversion();
        $opcacheEnabled = function_exists('opcache_get_status') ? @opcache_get_status() : null;

        // Проверка версии PHP
        $check_results['php_version'] = [
            "required" => ">=7.4",
            "current" => $phpVersion
        ];

        // Проверка включенного opcache
        $check_results['opcache_enabled'] = [
            "required" => 'disabled',
            "current" => $opcacheEnabled ? 'enabled' : 'disabled'
        ];

        return $check_results;
    }
    protected function extsForStepTwo()
    {
        $extensions = array(
            "pdo" => false,
            "pdo_mysql" => false,
            "mysqli" => false,
            "mbstring" => false,
            "json" => false,
            "curl" => false,
            "gd" => false,
            "intl" => false,
            "xml" => false,
            "zip" => false,
            "gmp" => false,
            "dom" => false,
            "iconv" => false,
            "simplexml" => false,
            "fileinfo" => false,
            "tokenizer" => false,
            "ctype" => false,
            "session" => false,
            "bcmath" => false,
            "openssl" => false,
        );

        $load_exts = [];

        $bad = 0;

        $recommended = ["dom", "gd", "intl", "iconv", "simplexml", "fileinfo", "tokenizer", "ctype", "session", "bcmath", "openssl"];

        foreach ($extensions as $extension => $loaded) {
            if (extension_loaded($extension)) {
                $load_exts[$extension] = [
                    "type" => "loaded"
                ];
            } else if (in_array($extension, $recommended)) {
                $load_exts[$extension] = [
                    "type" => "recommended"
                ];
            } else {
                $load_exts[$extension] = [
                    "type" => "disabled"
                ];

                $bad++;
            }
        }

        // Сортировка расширений
        uasort($load_exts, function ($a, $b) {
            $order = [
                "disabled" => 0,
                "recommended" => 1,
                "loaded" => 2
            ];

            return $order[$a['type']] - $order[$b['type']];
        });

        return [
            "list" => $load_exts,
            "bad" => $bad,
        ];
    }

    private function getEngineVersion(): string
    {
        return App::VERSION;
    }

    private function getTemplates(): array
    {
        return app(ThemeManager::class)->getAllThemes();
    }

    private function getModules(): array
    {
        return app(ModuleManager::class)->getModules()->toArray();
    }

    private function getLastLogEntries(string $logFilePath): string
    {
        $file = new \SplFileObject($logFilePath, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();

        $lines = [];
        for ($i = 0; $i < 20 && $lastLine - $i > 0; $i++) {
            $file->seek($lastLine - $i);
            array_unshift($lines, trim($file->current()));
        }

        return implode("\n\n", $lines);
    }
}