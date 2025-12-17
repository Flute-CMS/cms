<?php

namespace Flute\Admin\Packages\AboutSystem\Screens;

use Flute\Admin\Packages\AboutSystem\Helpers\AboutSystemHelper;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Core\Charts\FluteChart;

class AboutSystemScreen extends Screen
{
    /**
     * Screen title
     */
    protected ?string $name = null;

    /**
     * Screen description
     */
    protected ?string $description = null;

    /**
     * Mount the screen
     */
    public function mount(): void
    {
        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-about-system.labels.home'));
    }

    /**
     * Query data for the screen
     */
    public function query(): array
    {
        $performanceData = AboutSystemHelper::getPerformanceChartData();

        return [
            'systemInfo' => AboutSystemHelper::getSystemInfo(),
            'phpInfo' => AboutSystemHelper::getPhpInfo(),
            'serverInfo' => AboutSystemHelper::getServerInfo(),
            'requiredExtensions' => AboutSystemHelper::getRequiredExtensions(),
            'phpVersionValid' => AboutSystemHelper::checkPhpVersion(),
            'phpWarnings' => AboutSystemHelper::getPhpSettingWarnings(),
            'systemHealth' => AboutSystemHelper::getSystemHealth(),
            'resourceUsage' => AboutSystemHelper::getResourceUsage(),
            'performanceData' => $performanceData,
            'routesChart' => $this->buildRoutesChart($performanceData['routes']),
            'widgetsChart' => $this->buildWidgetsChart($performanceData['widgets']),
            'modulesChart' => $this->buildModulesChart($performanceData['modules']),
            'providersChart' => $this->buildProvidersChart($performanceData['providers']),
            'queriesChart' => $this->buildQueriesChart($performanceData['queries']),
        ];
    }

    /**
     * Get the layout elements
     */
    public function layout(): array
    {
        return [
            LayoutFactory::view('admin-about-system::index', $this->query()),
        ];
    }

    protected function buildRoutesChart(array $data): ?FluteChart
    {
        if (empty($data['labels'])) {
            return null;
        }

        $chart = new FluteChart();
        $chart->setType('bar')
            ->setHeight(300)
            ->setHorizontal(true)
            ->setColors(['#3b82f6'])
            ->setDataset([
                ['name' => __('admin-about-system.charts.avg_time'), 'data' => $data['avgTimes']],
            ])
            ->setXAxis($data['labels']);

        return $chart;
    }

    protected function buildWidgetsChart(array $data): ?FluteChart
    {
        if (empty($data['labels'])) {
            return null;
        }

        $chart = new FluteChart();
        $chart->setType('bar')
            ->setHeight(300)
            ->setHorizontal(true)
            ->setColors(['#10b981'])
            ->setDataset([
                ['name' => __('admin-about-system.charts.avg_time'), 'data' => $data['avgTimes']],
            ])
            ->setXAxis($data['labels']);

        return $chart;
    }

    protected function buildModulesChart(array $data): ?FluteChart
    {
        if (empty($data['labels'])) {
            return null;
        }

        $chart = new FluteChart();
        $chart->setType('bar')
            ->setHeight(300)
            ->setHorizontal(true)
            ->setColors(['#f59e0b'])
            ->setDataset([
                ['name' => __('admin-about-system.charts.boot_time'), 'data' => $data['avgTimes']],
            ])
            ->setXAxis($data['labels']);

        return $chart;
    }

    protected function buildProvidersChart(array $data): ?FluteChart
    {
        if (empty($data['labels'])) {
            return null;
        }

        $chart = new FluteChart();
        $chart->setType('bar')
            ->setHeight(300)
            ->setHorizontal(true)
            ->setColors(['#8b5cf6'])
            ->setDataset([
                ['name' => __('admin-about-system.charts.boot_time'), 'data' => $data['avgTimes']],
            ])
            ->setXAxis($data['labels']);

        return $chart;
    }

    protected function buildQueriesChart(array $data): ?FluteChart
    {
        if (empty($data['labels'])) {
            return null;
        }

        $chart = new FluteChart();
        $chart->setType('bar')
            ->setHeight(300)
            ->setHorizontal(true)
            ->setColors(['#ef4444'])
            ->setDataset([
                ['name' => __('admin-about-system.charts.avg_time'), 'data' => $data['avgTimes']],
            ])
            ->setXAxis($data['labels']);

        return $chart;
    }
}
