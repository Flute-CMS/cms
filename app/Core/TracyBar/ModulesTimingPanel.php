<?php

namespace Flute\Core\TracyBar;

use Flute\Core\App;
use Flute\Core\ModulesManager\ModuleRegister;
use Flute\Core\Profiling\GlobalProfiler;
use Tracy\IBarPanel;

class ModulesTimingPanel implements IBarPanel
{
    /** @var App */
    protected App $app;

    /** @var array Core components processing time */
    protected array $coreComponentTimes;

    public function __construct()
    {
        $this->app = app();
        $this->initCoreComponentTimes();
    }

    /**
     * Initialize core component timings
     */
    protected function initCoreComponentTimes(): void
    {
        // Default core component times structure
        $this->coreComponentTimes = [
            'Bootstrapping' => 0,
            'Container Building' => 0,
            'Routing' => 0,
            'Database' => 0,
            'View Rendering' => 0,
            'Untracked Operations' => 0,
        ];

        if (defined('FLUTE_ROUTER_START') && defined('FLUTE_ROUTER_END')) {
            $this->coreComponentTimes['Routing'] = constant('FLUTE_ROUTER_END') - constant('FLUTE_ROUTER_START');
        }

        if (defined('FLUTE_CONTAINER_START') && defined('FLUTE_CONTAINER_END')) {
            $this->coreComponentTimes['Container Building'] = constant('FLUTE_CONTAINER_END') - constant('FLUTE_CONTAINER_START');
        }

        if (defined('FLUTE_BOOTSTRAP_START') && defined('FLUTE_START')) {
            $this->coreComponentTimes['Bootstrapping'] = constant('FLUTE_BOOTSTRAP_START') - constant('FLUTE_START');
        }

        if (defined('FLUTE_DB_TIME')) {
            $this->coreComponentTimes['Database'] = constant('FLUTE_DB_TIME');
        }

        if (defined('FLUTE_VIEW_TIME')) {
            $this->coreComponentTimes['View Rendering'] = constant('FLUTE_VIEW_TIME');
        }

        $measuredCore = array_sum(array_filter($this->coreComponentTimes, fn ($k) => $k !== 'Untracked Operations', ARRAY_FILTER_USE_KEY));
        if (defined('FLUTE_START')) {
            $this->coreComponentTimes['Untracked Operations'] = max(0, (microtime(true) - constant('FLUTE_START')) - $measuredCore);
        }

        // Gather DB time measured via timing logger
        if (class_exists(\Flute\Core\Database\DatabaseTimingLogger::class)) {
            $dbTime = \Flute\Core\Database\DatabaseTimingLogger::getTotalTime();
            if ($dbTime > 0) {
                $this->coreComponentTimes['Database'] = $dbTime;
            }
        }
    }

    /**
     * Renders HTML code for the Tracy panel tab
     *
     * @return string
     */
    public function getTab(): string
    {
        return '<span title="Timing of modules and engine components">
            <svg viewBox="0 0 24 24" style="display:inline-block;width:16px;height:16px;vertical-align:middle">
                <path fill="#3cb371" d="M12 20c4.4 0 8-3.6 8-8s-3.6-8-8-8-8 3.6-8 8 3.6 8 8 8zm0-18c5.5 0 10 4.5 10 10s-4.5 10-10 10S2 17.5 2 12 6.5 2 12 2zm.5 5v5.25l4.5 2.67-.75 1.23L11 13V7h1.5z"/>
            </svg>
            <span class="tracy-label">Timing</span>
        </span>';
    }

    /**
     * Renders HTML code for the Tracy panel content
     *
     * @return string
     */
    public function getPanel(): string
    {
        $this->initCoreComponentTimes();

        if (class_exists(\Flute\Core\Template\TemplateAssets::class)) {
            $assetsTime = \Flute\Core\Template\TemplateAssets::getAssetsCompileTime();
            if ($assetsTime > 0) {
                $this->coreComponentTimes['Assets Compilation'] = $assetsTime;
            }
        }

        $bootTimes = $this->app->getBootTimes();
        $modulesBootTimes = ModuleRegister::getModulesBootTimes();

        // Filter out zero values for better visibility
        $bootTimes = array_filter($bootTimes, function ($time) {
            return $time > 0.0001; // Keep only significant numbers
        });

        $modulesBootTimes = array_filter($modulesBootTimes, function ($time) {
            return $time > 0.0001;
        });

        $id = uniqid('tracy-timing-');

        $html = '';

        // Tabs with unique IDs to avoid conflicts with other panels
        $html .= '<div class="tracy-tabs" id="' . $id . '">';
        $html .= '<ul class="tracy-tab-bar">';
        $html .= '<li class="tracy-tab"><a href="#' . $id . '-summary">Summary</a></li>';
        $html .= '<li class="tracy-tab"><a href="#' . $id . '-core">Core Components</a></li>';
        $html .= '<li class="tracy-tab"><a href="#' . $id . '-providers">Providers</a></li>';
        $html .= '<li class="tracy-tab"><a href="#' . $id . '-modules">Modules</a></li>';
        $html .= '<li class="tracy-tab"><a href="#' . $id . '-functions">Functions</a></li>';
        $html .= '<li class="tracy-tab"><a href="#' . $id . '-global">Global Profiling</a></li>';
        $html .= '<li class="tracy-tab"><a href="#' . $id . '-views">Views</a></li>';
        $html .= '<li class="tracy-tab"><a href="#' . $id . '-routing">Routing</a></li>';
        $html .= '</ul>';

        // Summary tab content
        $html .= '<div class="tracy-tab-panel" id="' . $id . '-summary">';
        $html .= $this->renderSummaryPanel();
        $html .= '</div>';

        // Core components tab content
        $html .= '<div class="tracy-tab-panel" id="' . $id . '-core">';
        $html .= $this->renderCoreComponentsPanel();
        $html .= '</div>';

        // Providers tab content
        $html .= '<div class="tracy-tab-panel" id="' . $id . '-providers">';

        if (empty($bootTimes)) {
            $html .= '<div class="tracy-inner">No data about provider loading time</div>';
        } else {
            // Sort by loading time (highest to lowest)
            arsort($bootTimes);

            $totalTime = array_sum($bootTimes);
            $html .= $this->renderHeader('Providers loading time', $totalTime);

            // Providers table
            $html .= '<div class="tracy-inner"><table style="width:100%">';
            $html .= '<tr><th>Service provider</th><th>Time (sec)</th><th>%</th><th></th></tr>';

            foreach ($bootTimes as $provider => $time) {
                $percent = ($time / $totalTime) * 100;
                $shortName = $this->getShortClassName($provider);
                $isModule = strpos($provider, 'Modules\\') !== false;

                $html .= sprintf(
                    '<tr><td title="%s">%s %s</td><td>%.3f</td><td>%.1f%%</td><td><div style="background:#3cb371;height:4px;width:%d%%;"></div></td></tr>',
                    htmlspecialchars($provider),
                    htmlspecialchars($shortName),
                    $isModule ? '(module)' : '',
                    $time,
                    $percent,
                    min(100, $percent * 1.5)
                );
            }

            $html .= '</table></div>';
        }

        $html .= '</div>';

        // Modules tab content
        $html .= '<div class="tracy-tab-panel" id="' . $id . '-modules">';

        if (empty($modulesBootTimes)) {
            $html .= '<div class="tracy-inner">No data about module loading time</div>';
        } else {
            // Sort by loading time (highest to lowest)
            arsort($modulesBootTimes);

            $totalModulesTime = array_sum($modulesBootTimes);
            $html .= $this->renderHeader('Modules loading time', $totalModulesTime);

            // Modules table
            $html .= '<div class="tracy-inner"><table style="width:100%">';
            $html .= '<tr><th>Module</th><th>Time (sec)</th><th>%</th><th></th></tr>';

            foreach ($modulesBootTimes as $module => $time) {
                $percent = ($time / $totalModulesTime) * 100;

                $html .= sprintf(
                    '<tr><td>%s</td><td>%.3f</td><td>%.1f%%</td><td><div style="background:#3cb371;height:4px;width:%d%%;"></div></td></tr>',
                    htmlspecialchars($module),
                    $time,
                    $percent,
                    min(100, $percent * 1.5)
                );
            }

            $html .= '</table></div>';
        }

        $html .= '</div>';

        $html .= '<div class="tracy-tab-panel" id="' . $id . '-views">';
        $viewTimes = \Flute\Core\Template\TemplateRenderTiming::all();
        if (empty($viewTimes)) {
            $html .= '<div class="tracy-inner">No view render timings collected</div>';
        } else {
            arsort($viewTimes);
            $totalViewTime = array_sum($viewTimes);
            $html .= $this->renderHeader('Blade views render time', $totalViewTime);

            $html .= '<div class="tracy-inner"><table style="width:100%">';
            $html .= '<tr><th>View</th><th>Time (sec)</th><th>%</th><th></th></tr>';
            foreach ($viewTimes as $view => $time) {
                $percent = ($time / $totalViewTime) * 100;
                $short = htmlspecialchars($view);
                $html .= sprintf('<tr><td>%s</td><td>%.4f</td><td>%.1f%%</td><td><div style="background:#FFB347;height:4px;width:%d%%;"></div></td></tr>', $short, $time, $percent, min(100, $percent * 1.5));
            }
            $html .= '</table></div>';
        }
        $html .= '</div>';

        // Routing tab content
        $html .= '<div class="tracy-tab-panel" id="' . $id . '-routing">';
        $rTimes = \Flute\Core\Router\RoutingTiming::all();
        if (empty($rTimes)) {
            $html .= '<div class="tracy-inner">No routing timings collected</div>';
        } else {
            arsort($rTimes);
            $totalRT = array_sum($rTimes);
            $html .= $this->renderHeader('Routing pipeline', $totalRT);
            $html .= '<div class="tracy-inner"><table style="width:100%">';
            $html .= '<tr><th>Segment</th><th>Time (sec)</th><th>%</th><th></th></tr>';
            foreach ($rTimes as $seg => $time) {
                $percent = ($time / $totalRT) * 100;
                $html .= sprintf('<tr><td>%s</td><td>%.4f</td><td>%.1f%%</td><td><div style="background:#8FBC8F;height:4px;width:%d%%;"></div></td></tr>', htmlspecialchars($seg), $time, $percent, min(100, $percent * 1.5));
            }
            $html .= '</table></div>';
        }
        $html .= '</div>';

        $html .= '</div>';

        // Add script to initialize tabs
        $html .= "<script>
        (function() {
            setTimeout(function() {
                var tabs = document.getElementById('" . $id . "');
                if (tabs) {
                    var links = tabs.querySelectorAll('.tracy-tab a');
                    var panels = tabs.querySelectorAll('.tracy-tab-panel');
                    
                    // Set first tab as active
                    if (links.length > 0) {
                        links[0].parentNode.classList.add('tracy-active');
                        panels[0].classList.add('tracy-active');
                    }
                    
                    // Tab click handler
                    Array.prototype.forEach.call(links, function(link) {
                        link.addEventListener('click', function(e) {
                            e.preventDefault();
                            
                            // Remove active class from all tabs
                            Array.prototype.forEach.call(links, function(l) {
                                l.parentNode.classList.remove('tracy-active');
                            });
                            
                            // Remove active class from all panels
                            Array.prototype.forEach.call(panels, function(p) {
                                p.classList.remove('tracy-active');
                            });
                            
                            // Add active class to current tab
                            this.parentNode.classList.add('tracy-active');
                            
                            // Activate corresponding panel
                            var target = this.getAttribute('href').substring(1);
                            document.getElementById(target).classList.add('tracy-active');
                        });
                    });
                }
            }, 100);
        })();
        </script>";

        return $html;
    }

    /**
     * Renders the summary panel with total engine time
     *
     * @return string
     */
    private function renderSummaryPanel(): string
    {
        $totalEngineTime = microtime(true) - FLUTE_START;
        $bootTimes = $this->app->getBootTimes();
        $modulesBootTimes = ModuleRegister::getModulesBootTimes();

        $totalProvidersTime = array_sum($bootTimes);
        $totalModulesTime = array_sum($modulesBootTimes);
        $totalCoreTime = array_sum($this->coreComponentTimes);

        $otherTime = $totalEngineTime - $totalProvidersTime - $totalModulesTime - $totalCoreTime;
        if ($otherTime < 0) {
            $otherTime = 0;
        }

        $html = '<div class="tracy-inner">';
        $html .= '<h1>Total engine loading time: ' . sprintf('%.3f', $totalEngineTime) . ' sec</h1>';

        $html .= '<table style="width:100%">';
        $html .= '<tr><th>Component</th><th>Time (sec)</th><th>%</th><th></th></tr>';

        // Core
        $corePercent = ($totalCoreTime / $totalEngineTime) * 100;
        $html .= sprintf(
            '<tr><td>Core Components</td><td>%.3f</td><td>%.1f%%</td><td><div style="background:#FF7F50;height:8px;width:%d%%;"></div></td></tr>',
            $totalCoreTime,
            $corePercent,
            min(100, $corePercent)
        );

        // Providers
        $providersPercent = ($totalProvidersTime / $totalEngineTime) * 100;
        $html .= sprintf(
            '<tr><td>Service Providers</td><td>%.3f</td><td>%.1f%%</td><td><div style="background:#3cb371;height:8px;width:%d%%;"></div></td></tr>',
            $totalProvidersTime,
            $providersPercent,
            min(100, $providersPercent)
        );

        // Modules
        $modulesPercent = ($totalModulesTime / $totalEngineTime) * 100;
        $html .= sprintf(
            '<tr><td>Modules</td><td>%.3f</td><td>%.1f%%</td><td><div style="background:#4169E1;height:8px;width:%d%%;"></div></td></tr>',
            $totalModulesTime,
            $modulesPercent,
            min(100, $modulesPercent)
        );

        // Other
        $otherPercent = ($otherTime / $totalEngineTime) * 100;
        $html .= sprintf(
            '<tr><td>Untracked Operations</td><td>%.3f</td><td>%.1f%%</td><td><div style="background:#808080;height:8px;width:%d%%;"></div></td></tr>',
            $otherTime,
            $otherPercent,
            min(100, $otherPercent)
        );

        $html .= '</table>';

        $html .= '<div style="margin-top:20px">';
        $html .= '<h2>Loading Timeline</h2>';
        $html .= '<div style="display:flex;height:30px;width:100%;margin-top:10px;">';
        $html .= sprintf('<div title="Core Components: %.1f%%" style="background:#FF7F50;height:100%%;width:%.1f%%"></div>', $corePercent, $corePercent);
        $html .= sprintf('<div title="Service Providers: %.1f%%" style="background:#3cb371;height:100%%;width:%.1f%%"></div>', $providersPercent, $providersPercent);
        $html .= sprintf('<div title="Modules: %.1f%%" style="background:#4169E1;height:100%%;width:%.1f%%"></div>', $modulesPercent, $modulesPercent);
        $html .= sprintf('<div title="Untracked Operations: %.1f%%" style="background:#808080;height:100%%;width:%.1f%%"></div>', $otherPercent, $otherPercent);
        $html .= '</div>';

        $html .= '<div style="display:flex;margin-top:5px;font-size:12px;">';
        $html .= '<div style="margin-right:15px;"><span style="display:inline-block;width:10px;height:10px;background:#FF7F50;margin-right:5px;"></span>Core Components</div>';
        $html .= '<div style="margin-right:15px;"><span style="display:inline-block;width:10px;height:10px;background:#3cb371;margin-right:5px;"></span>Service Providers</div>';
        $html .= '<div style="margin-right:15px;"><span style="display:inline-block;width:10px;height:10px;background:#4169E1;margin-right:5px;"></span>Modules</div>';
        $html .= '<div><span style="display:inline-block;width:10px;height:10px;background:#808080;margin-right:5px;"></span>Untracked Operations</div>';
        $html .= '</div>';

        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Renders the core components panel with detailed timing
     *
     * @return string
     */
    private function renderCoreComponentsPanel(): string
    {
        $totalCoreTime = array_sum($this->coreComponentTimes);

        $html = '<div class="tracy-inner">';
        $html .= '<h1>Core components timing: ' . sprintf('%.3f', $totalCoreTime) . ' sec</h1>';

        if ($totalCoreTime == 0) {
            $totalEngineTime = microtime(true) - FLUTE_START;
            $bootTimes = $this->app->getBootTimes();
            $modulesBootTimes = ModuleRegister::getModulesBootTimes();

            $totalProvidersTime = array_sum($bootTimes);
            $totalModulesTime = array_sum($modulesBootTimes);

            $estimatedCoreTime = $totalEngineTime - $totalProvidersTime - $totalModulesTime;
            if ($estimatedCoreTime < 0) {
                $estimatedCoreTime = 0;
            }

            $html .= '<p>No detailed core timing data available. Estimated core time: ' .
                     sprintf('%.3f', $estimatedCoreTime) . ' sec</p>';

            $html .= '<div style="margin-top:15px;background:#F8F8FF;padding:10px;border-left:4px solid #FF7F50;border-radius:3px">';
            $html .= '<h2>Performance Monitoring Tips</h2>';
            $html .= '<p>To get more detailed core component timing, add the following constants to track timing in your code:</p>';
            $html .= '<ul>';
            $html .= '<li><code>define(\'FLUTE_BOOTSTRAP_START\', microtime(true));</code> - At the start of bootstrap process</li>';
            $html .= '<li><code>define(\'FLUTE_CONTAINER_START\', microtime(true));</code> - Before container building</li>';
            $html .= '<li><code>define(\'FLUTE_CONTAINER_END\', microtime(true));</code> - After container building</li>';
            $html .= '<li><code>define(\'FLUTE_ROUTER_START\', microtime(true));</code> - Before routing</li>';
            $html .= '<li><code>define(\'FLUTE_ROUTER_END\', microtime(true));</code> - After routing</li>';
            $html .= '<li><code>define(\'FLUTE_DB_TIME\', $timeSpentOnDatabase);</code> - For database operations</li>';
            $html .= '<li><code>define(\'FLUTE_VIEW_TIME\', $timeSpentOnViewRendering);</code> - For view rendering</li>';
            $html .= '</ul>';
            $html .= '</div>';

            return $html . '</div>';
        }

        $html .= '<table style="width:100%">';
        $html .= '<tr><th>Component</th><th>Time (sec)</th><th>%</th><th></th></tr>';

        arsort($this->coreComponentTimes);

        foreach ($this->coreComponentTimes as $component => $time) {
            if ($time <= 0) {
                continue;
            }

            $percent = ($time / $totalCoreTime) * 100;

            $html .= sprintf(
                '<tr><td>%s</td><td>%.3f</td><td>%.1f%%</td><td><div style="background:%s;height:4px;width:%d%%;"></div></td></tr>',
                htmlspecialchars($component),
                $time,
                $percent,
                $component === 'Untracked Operations' ? '#808080' : '#FF7F50',
                min(100, $percent * 1.5)
            );
        }

        $html .= '</table>';

        $html .= $this->renderCoreOptimizationSuggestions();

        $html .= '</div>';

        return $html;
    }

    /**
     * Render core optimization suggestions based on bottlenecks
     *
     * @return string
     */
    private function renderCoreOptimizationSuggestions(): string
    {
        $html = '<div style="margin-top:20px;background:#F8F8FF;padding:10px;border-left:4px solid #FF7F50;border-radius:3px">';
        $html .= '<h2>Performance Optimization Suggestions</h2>';

        // Clone and sort to find the slowest component
        $coreTimes = $this->coreComponentTimes;
        arsort($coreTimes);
        $slowestComponent = key($coreTimes);
        $slowestTime = current($coreTimes);

        if ($slowestTime > 0) {
            $html .= '<p>The slowest core component is <strong>' . htmlspecialchars($slowestComponent) .
                     '</strong> (' . sprintf('%.3f', $slowestTime) . ' sec).</p>';

            // Specific optimization suggestions based on the bottleneck
            $html .= '<h3>Optimization tips:</h3><ul>';

            switch ($slowestComponent) {
                case 'Bootstrapping':
                    $html .= '<li>Reduce the number of files loaded during bootstrap</li>';
                    $html .= '<li>Consider lazy loading more components</li>';
                    $html .= '<li>Check for slow autoloading or excessive class lookups</li>';

                    break;

                case 'Container Building':
                    $html .= '<li>Reduce the number of services registered in the container</li>';
                    $html .= '<li>Use container compilation in production</li>';
                    $html .= '<li>Consider deferring service registration where possible</li>';

                    break;

                case 'Routing':
                    $html .= '<li>Simplify routing rules</li>';
                    $html .= '<li>Cache compiled routes</li>';
                    $html .= '<li>Reduce middleware overhead in the routing process</li>';

                    break;

                case 'Database':
                    $html .= '<li>Optimize database queries</li>';
                    $html .= '<li>Add indexes to frequently queried columns</li>';
                    $html .= '<li>Consider query caching for frequently accessed data</li>';

                    break;

                case 'View Rendering':
                    $html .= '<li>Simplify view templates</li>';
                    $html .= '<li>Cache rendered views where appropriate</li>';
                    $html .= '<li>Reduce the use of expensive helpers in templates</li>';

                    break;

                default:
                    $html .= '<li>Profile the application to identify specific bottlenecks</li>';
                    $html .= '<li>Consider enabling opcache for better PHP performance</li>';
                    $html .= '<li>Review error and debug logging settings in production</li>';
            }

            $html .= '</ul>';
        } else {
            $html .= '<p>No specific bottlenecks identified. Consider adding timing constants for more detailed analysis.</p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renders header with total time
     *
     * @param string $title
     * @param float $totalTime
     * @return string
     */
    private function renderHeader(string $title, float $totalTime): string
    {
        return sprintf(
            '<h1>%s (total: %.3f sec)</h1>',
            $title,
            $totalTime
        );
    }

    /**
     * Renders the global profiling panel
     *
     * @return string
     */
    private function renderGlobalProfilingPanel(): string
    {
        if (!GlobalProfiler::isEnabled()) {
            $html = '<div class="tracy-inner">';
            $html .= '<h1>Global Profiling Not Active</h1>';
            $html .= '<p>Global profiling is only available in debug mode.</p>';
            $html .= '</div>';

            return $html;
        }

        $functionTimings = GlobalProfiler::getFunctionTimings();
        $topSlow = GlobalProfiler::getTopSlowFunctions(15);

        $html = '<div class="tracy-inner">';

        // Top slowest functions
        if (!empty($topSlow)) {
            $html .= '<h1>Top Slowest Functions</h1>';
            $html .= '<table style="width:100%">';
            $html .= '<tr><th>Function</th><th>Category</th><th>Time (sec)</th><th>Calls</th><th>Memory</th><th></th></tr>';

            $maxTime = $topSlow[0]['wall_time'] ?? 1;
            foreach ($topSlow as $func) {
                $percent = ($func['wall_time'] / $maxTime) * 100;
                $shortName = $this->getShortClassName($func['function']);
                $memory = $this->formatBytes($func['memory']);

                $html .= sprintf(
                    '<tr><td title="%s">%s</td><td>%s</td><td>%.4f</td><td>%d</td><td>%s</td><td><div style="background:#FF6B6B;height:4px;width:%d%%;"></div></td></tr>',
                    htmlspecialchars($func['function']),
                    htmlspecialchars($shortName),
                    htmlspecialchars($func['category']),
                    $func['wall_time'],
                    $func['calls'],
                    $memory,
                    min(100, $percent)
                );
            }
            $html .= '</table>';
        }

        // Functions grouped by category
        if (!empty($functionTimings)) {
            $html .= '<h1 style="margin-top:20px">Functions by Category</h1>';

            foreach ($functionTimings as $category => $functions) {
                if (empty($functions)) {
                    continue;
                }

                $categoryTotal = array_sum(array_column($functions, 'wall_time'));
                $html .= '<h2>' . htmlspecialchars($category) . ' (' . sprintf('%.3f', $categoryTotal) . ' sec)</h2>';

                $html .= '<table style="width:100%;margin-bottom:15px">';
                $html .= '<tr><th>Function</th><th>Time (sec)</th><th>CPU (sec)</th><th>Calls</th><th>Memory</th><th></th></tr>';

                $maxCategoryTime = max(array_column($functions, 'wall_time'));
                foreach ($functions as $funcName => $data) {
                    $percent = ($data['wall_time'] / $maxCategoryTime) * 100;
                    $shortName = $this->getShortClassName($funcName);
                    $memory = $this->formatBytes($data['memory']);

                    $html .= sprintf(
                        '<tr><td title="%s">%s</td><td>%.4f</td><td>%.4f</td><td>%d</td><td>%s</td><td><div style="background:#4ECDC4;height:4px;width:%d%%;"></div></td></tr>',
                        htmlspecialchars($funcName),
                        htmlspecialchars($shortName),
                        $data['wall_time'],
                        $data['cpu_time'],
                        $data['calls'],
                        $memory,
                        min(100, $percent)
                    );
                }
                $html .= '</table>';
            }
        }

        if (empty($topSlow) && empty($functionTimings)) {
            $html .= '<h1>No Profiling Data</h1>';
            $html .= '<p>No function timing data was collected. Make sure a profiler extension (tideways_xhprof, xhprof, etc.) is installed.</p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    /**
     * Get short class name from fully qualified name
     *
     * @param string $className
     * @return string
     */
    private function getShortClassName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts);
    }
}
