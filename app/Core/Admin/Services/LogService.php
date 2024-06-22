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

        $htmlContent = $this->generateHtmlHeader();
        $htmlContent .= $this->generateSystemInformation();
        $htmlContent .= $this->generateRequirementsCheck();
        $htmlContent .= $this->generateExtensionsCheck();
        $htmlContent .= $this->generateComposerDependenciesCheck();
        $htmlContent .= $this->generateEngineInformation();
        $htmlContent .= $this->generateTemplates();
        $htmlContent .= $this->generateModules();
        $htmlContent .= $this->generateLogEntries($loggers);
        $htmlContent .= $this->generateHtmlFooter();

        $tempFilePath = BASE_PATH . 'storage/logs/' . now()->format('m-d-y-h-i-s') . '.html';
        FileSystem::write($tempFilePath, $htmlContent);

        return $tempFilePath;
    }

    public function downloadLogFile(string $filePath): BinaryFileResponse
    {
        $response = new BinaryFileResponse($filePath, Response::HTTP_OK, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'attachment; filename="system_log.html"'
        ], true, null, false, true);

        $response->deleteFileAfterSend(true);

        return $response;
    }

    protected function generateHtmlHeader(): string
    {
        return '
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>System Log</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 0 auto; padding: 20px; max-width: 1400px; background-color: #1e1e1e; color: #cfcfcf; }
                    h1, h2, h3 { color: #e2e2e2; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    th, td { border: 1px solid #444; padding: 8px; }
                    th { background-color: #333; color: #e2e2e2; }
                    .bad { color: #e74c3c; }
                    .good { color: #2ecc71; }
                    .warning { color: #f39c12; }
                    pre { background-color: #2e2e2e; padding: 10px; border-radius: 5px; white-space: pre-wrap; }
                </style>
            </head>
            <body>
        ';
    }

    protected function generateHtmlFooter(): string
    {
        return '
            </body>
            </html>
        ';
    }

    protected function generateSystemInformation(): string
    {
        $performanceModeWarning = is_performance() ? "<p class='warning'>Information may not be up to date (performance mode enabled).</p>" : "";
        $langCacheWarning = config('lang.cache') ? "<p class='warning'>Translation caching is enabled!</p>" : "";

        return "
            <h1>System Information</h1>
            {$performanceModeWarning}
            {$langCacheWarning}
            <p>Loaded Extensions: " . implode(', ', get_loaded_extensions()) . "</p>
        ";
    }

    protected function generateRequirementsCheck(): string
    {
        $reqs = $this->reqsForStepTwo();
        $html = "
            <h2>Requirements Check</h2>
            <table>
                <tr>
                    <th>Requirement</th>
                    <th>Required</th>
                    <th>Current</th>
                </tr>
        ";

        foreach ($reqs as $key => $value) {
            $statusClass = version_compare($value['current'], $value['required'], '>=') ? 'good' : 'bad';
            $html .= "
                <tr>
                    <td>{$key}</td>
                    <td>{$value['required']}</td>
                    <td class=\"{$statusClass}\">{$value['current']}</td>
                </tr>
            ";
        }

        $html .= "</table>";
        return $html;
    }

    protected function generateExtensionsCheck(): string
    {
        $exts = $this->extsForStepTwo();
        $html = "
            <h2>Extensions Check</h2>
            <table>
                <tr>
                    <th>Extension</th>
                    <th>Status</th>
                </tr>
        ";

        foreach ($exts['list'] as $extension => $info) {
            $statusClass = ($info['type'] == 'loaded') ? 'good' : (($info['type'] == 'recommended') ? 'warning' : 'bad');
            $html .= "
                <tr>
                    <td>{$extension}</td>
                    <td class=\"{$statusClass}\">" . ucfirst($info['type']) . "</td>
                </tr>
            ";
        }

        $html .= "</table>";
        $html .= "<p>Number of Bad Extensions: <span class=\"bad\">{$exts['bad']}</span></p>";
        return $html;
    }

    protected function generateComposerDependenciesCheck(): string
    {
        $deps = $this->checkComposerDependencies();
        $html = "
            <h2>Composer Dependencies Check</h2>
            <table>
                <tr>
                    <th>Dependency</th>
                    <th>Status</th>
                </tr>
        ";

        foreach ($deps as $dependency => $status) {
            $statusClass = $status ? 'good' : 'bad';
            $html .= "
                <tr>
                    <td>{$dependency}</td>
                    <td class=\"{$statusClass}\">" . ($status ? 'installed' : 'missing') . "</td>
                </tr>
            ";
        }

        $html .= "</table>";
        return $html;
    }

    protected function generateEngineInformation(): string
    {
        return "
            <h2>Engine Information</h2>
            <p>Flute Version: " . $this->getEngineVersion() . "</p>
            <p>Performance mode: " . (is_performance() ? 'active' : 'disabled') . "</p>
            <p>Debug mode: " . (is_debug() ? 'active' : 'disabled') . "</p>
        ";
    }

    protected function generateTemplates(): string
    {
        $templates = $this->getTemplates();
        $html = "
            <h2>Templates</h2>
            <table>
                <tr>
                    <th>Key</th>
                    <th>Name</th>
                    <th>Version</th>
                    <th>Status</th>
                </tr>
        ";

        foreach ($templates as $template) {
            $html .= "
                <tr>
                    <td>{$template->key}</td>
                    <td>{$template->name}</td>
                    <td>{$template->version}</td>
                    <td>{$template->status}</td>
                </tr>
            ";
        }

        $html .= "</table>";
        return $html;
    }

    protected function generateModules(): string
    {
        $modules = $this->getModules();
        $html = "
            <h2>Modules</h2>
            <table>
                <tr>
                    <th>Key</th>
                    <th>Name</th>
                    <th>Version</th>
                    <th>Status</th>
                </tr>
        ";

        foreach ($modules as $module) {
            $html .= "
                <tr>
                    <td>{$module->key}</td>
                    <td>{$module->name}</td>
                    <td>{$module->version}</td>
                    <td>{$module->status}</td>
                </tr>
            ";
        }

        $html .= "</table>";
        return $html;
    }

    protected function generateLogEntries($loggers): string
    {
        $html = "<h2>Last 20 Errors from Monolog</h2>";

        foreach ($loggers as $loggerName => $loggerConfig) {
            $html .= "<h3>Last 20 Entries for Logger - {$loggerName}</h3>";
            $html .= "<pre>" . $this->getLastLogEntries($loggerConfig['path']) . "</pre>";
        }

        return $html;
    }

    protected function reqsForStepTwo()
    {
        $check_results = [];

        $phpVersion = phpversion();
        $opcacheEnabled = function_exists('opcache_get_status') ? @opcache_get_status() : null;

        $check_results['php_version'] = [
            "required" => ">=7.4",
            "current" => $phpVersion
        ];

        $check_results['opcache_enabled'] = [
            "required" => 'disabled',
            "current" => $opcacheEnabled ? 'enabled' : 'disabled'
        ];

        return $check_results;
    }

    protected function extsForStepTwo()
    {
        $extensions = array(
            "pdo",
            "pdo_mysql",
            "mysqli",
            "mbstring",
            "json",
            "curl",
            "gd",
            "intl",
            "xml",
            "zip",
            "gmp",
            "dom",
            "iconv",
            "simplexml",
            "fileinfo",
            "tokenizer",
            "ctype",
            "session",
            "bcmath",
            "openssl"
        );

        $load_exts = [];
        $bad = 0;
        $recommended = ["dom", "gd", "intl", "iconv", "simplexml", "fileinfo", "tokenizer", "ctype", "session", "bcmath", "openssl"];

        foreach ($extensions as $extension) {
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

    protected function checkComposerDependencies()
    {
        $composerJsonPath = BASE_PATH . 'composer.json';
        $composerLockPath = BASE_PATH . 'composer.lock';

        $dependencies = [];
        if (file_exists($composerJsonPath)) {
            $composerJson = json_decode(file_get_contents($composerJsonPath), true);
            if (isset($composerJson['require'])) {
                $dependencies = array_keys($composerJson['require']);
            }
        }

        $installedDependencies = [];
        if (file_exists($composerLockPath)) {
            $composerLock = json_decode(file_get_contents($composerLockPath), true);
            if (isset($composerLock['packages'])) {
                foreach ($composerLock['packages'] as $package) {
                    $installedDependencies[] = $package['name'];
                }
            }
        }

        $dependenciesCheck = [];
        foreach ($dependencies as $dependency) {
            $dependenciesCheck[$dependency] = in_array($dependency, $installedDependencies);
        }

        return $dependenciesCheck;
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
