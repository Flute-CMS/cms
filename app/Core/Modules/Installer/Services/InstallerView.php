<?php

namespace Flute\Core\Modules\Installer\Services;

use Flute\Core\Template\Template;

/**
 * Service for handling installer views
 */
class InstallerView
{
    protected Template $template;

    protected InstallerConfig $installerConfig;

    /**
     * Step number to view name mapping
     */
    protected array $stepViews = [
        1 => 'installer::yoyo.system-check',
        2 => 'installer::yoyo.database',
        3 => 'installer::yoyo.account-site',
        4 => 'installer::yoyo.languages',
        5 => 'installer::yoyo.modules',
        6 => 'installer::yoyo.launch',
    ];

    public function __construct(Template $template, InstallerConfig $installerConfig)
    {
        $this->template = $template;
        $this->installerConfig = $installerConfig;
    }

    /**
     * Render the installer layout with given data
     */
    public function render(array $data = [], ?int $currentStep = null): string
    {
        $step = $this->installerConfig->getCurrentStep();
        $totalSteps = $this->installerConfig->getTotalSteps();

        $defaultData = [
            'currentStep' => $currentStep,
            'step' => $step,
            'steps' => $this->stepViews,
            'totalSteps' => $totalSteps,
            'progress' => ( $step / $totalSteps ) * 100,
            'params' => $this->installerConfig->getParams(),
        ];

        return view('installer::layout', array_merge($defaultData, $data))->render();
    }

    /**
     * Render a step view
     */
    public function renderStep(int $step, array $data = []): string
    {
        $viewName = $this->stepViews[$step] ?? null;

        if ($viewName === null) {
            return '';
        }

        return $this->render([
            'stepView' => $viewName,
            'stepData' => $data,
        ], $step);
    }
}
