<?php

namespace Flute\Admin\Packages\AboutSystem\Controllers;

use Flute\Admin\Packages\AboutSystem\Controllers\Concerns\GeneratesEnvironmentInfo;
use Flute\Admin\Packages\AboutSystem\Controllers\Concerns\GeneratesModulesInfo;
use Flute\Admin\Packages\AboutSystem\Controllers\Concerns\GeneratesPlatformInfo;
use Flute\Admin\Packages\AboutSystem\Controllers\Concerns\GeneratesQueriesAndViewStats;
use Flute\Admin\Packages\AboutSystem\Controllers\Concerns\GeneratesRequestInfo;
use Flute\Admin\Packages\AboutSystem\Controllers\Concerns\GeneratesRoutesStats;
use Flute\Admin\Packages\AboutSystem\Controllers\Concerns\ReportFormatting;
use Flute\Admin\Packages\AboutSystem\Helpers\AboutSystemHelper;
use Flute\Admin\Packages\Logs\Services\LogViewerService;
use Flute\Core\ModulesManager\ModuleManager;
use Flute\Core\Support\BaseController;
use Flute\Core\Theme\ThemeManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class SystemReportController extends BaseController
{
    use GeneratesEnvironmentInfo;
    use GeneratesModulesInfo;
    use GeneratesPlatformInfo;
    use GeneratesQueriesAndViewStats;
    use GeneratesRequestInfo;
    use GeneratesRoutesStats;
    use ReportFormatting;

    protected ModuleManager $moduleManager;

    protected ThemeManager $themeManager;

    protected LogViewerService $logViewerService;

    public function __construct(
        ModuleManager $moduleManager,
        ThemeManager $themeManager,
        LogViewerService $logViewerService,
    ) {
        $this->moduleManager = $moduleManager;
        $this->themeManager = $themeManager;
        $this->logViewerService = $logViewerService;
    }

    public function download(): Response
    {
        $report = $this->generateReport();

        $basename = 'flute_system_report_' . date('Y-m-d_H-i-s');

        if (class_exists(\ZipArchive::class)) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'flute_report_');
            if (!is_string($tmpFile) || $tmpFile === '') {
                $tmpDir = storage_path('app/temp');
                if (!is_dir($tmpDir)) {
                    @mkdir($tmpDir, 0o775, true);
                }

                $tmpFile = tempnam($tmpDir, 'flute_report_');
            }

            $zip = new \ZipArchive();
            if (
                is_string($tmpFile)
                && $tmpFile !== ''
                && $zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true
            ) {
                $zip->addFromString($basename . '.txt', $report);
                $zip->close();

                $zipContent = file_get_contents($tmpFile);
                @unlink($tmpFile);

                if ($zipContent !== false) {
                    $response = new Response($zipContent);
                    $response->headers->set('Content-Type', 'application/zip');
                    $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
                        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                        $basename . '.zip',
                    ));

                    return $response;
                }
            }

            if (is_string($tmpFile) && $tmpFile !== '') {
                @unlink($tmpFile);
            }
        }

        $response = new Response($report);
        $response->headers->set('Content-Type', 'text/plain; charset=utf-8');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $basename . '.txt',
        ));

        return $response;
    }

    protected function generateReport(): string
    {
        $sections = [
            $this->generateHeader(),
            $this->generateSystemSection(),
            $this->generatePhpSection(),
            $this->generateServerSection(),
            $this->generateExtensionsSection(),
            $this->generateModulesSection(),
            $this->generateModulesPerformanceStatsSection(),
            $this->generateProvidersPerformanceStatsSection(),
            $this->generateRoutesPerformanceStatsSection(),
            $this->generateQueriesPerformanceStatsSection(),
            $this->generateWidgetsPerformanceStatsSection(),
            $this->generateViewsPerformanceStatsSection(),
            $this->generateThemesSection(),
            $this->generateDatabaseSection(),
            $this->generateCacheSection(),
            $this->generateComposerSection(),
            $this->generateDirectoriesSection(),
            $this->generateConfigSection(),
            $this->generateSessionSection(),
            $this->generateRequestSection(),
            $this->generateFullLogsSection(),
        ];

        return implode("\n\n", array_filter($sections));
    }

    protected function generateHeader(): string
    {
        $lines = [
            'FLUTE CMS SYSTEM REPORT',
            'Generated: ' . date('Y-m-d H:i:s T'),
            '',
        ];

        $lines[] = '--- IONCUBE STATUS ---';

        $ioncubeLoaded = extension_loaded('ionCube Loader');
        $lines[] = 'ionCube Loader: ' . ( $ioncubeLoaded ? 'LOADED' : 'NOT LOADED' );

        if ($ioncubeLoaded) {
            if (function_exists('ioncube_loader_version')) {
                $lines[] = 'ionCube Version: ' . ioncube_loader_version();
            }

            $encodedPaths = ini_get('ioncube.loader.encoded_paths');
            $modulesPath = path('app/Modules');
            $suggestedEncodedPath = AboutSystemHelper::normalizeFilesystemPathForComparison($modulesPath);
            if ($suggestedEncodedPath === '') {
                $suggestedEncodedPath = $modulesPath;
            }

            if ($encodedPaths) {
                $lines[] = 'encoded_paths: ***SET***';

                $paths = array_filter(array_map('trim', explode(PATH_SEPARATOR, $encodedPaths)));
                $modulesConfigured = false;

                foreach ($paths as $p) {
                    if (AboutSystemHelper::ioncubeEncodedPathMatchesModulesDir($p, $modulesPath)) {
                        $modulesConfigured = true;

                        break;
                    }
                }

                if ($modulesConfigured) {
                    $lines[] = 'Status: OK - Modules path is configured in encoded_paths';
                } else {
                    $lines[] = '*** WARNING: Modules path NOT in encoded_paths! ***';
                    $lines[] = '*** This causes ionCube to scan ALL files on EVERY request! ***';
                    $lines[] = '*** Add Modules path to php.ini ioncube.loader.encoded_paths ***';
                }
            } else {
                $lines[] = 'encoded_paths: NOT SET';
                $lines[] = '*** WARNING: encoded_paths is not configured! ***';
                $lines[] = '*** ionCube will scan ALL PHP files on EVERY request! ***';
                $lines[] = '*** This SIGNIFICANTLY degrades performance! ***';
                $lines[] = '*** Add Modules path to php.ini ioncube.loader.encoded_paths ***';
            }
        }

        $lines[] = '';
        $lines[] = 'Performance stats: auto-collected, cached 7 days (modules, providers, routes, queries, widgets, views).';

        return implode("\n", $lines);
    }
}
