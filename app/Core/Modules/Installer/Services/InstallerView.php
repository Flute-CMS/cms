<?php

namespace Flute\Core\Modules\Installer\Services;

use Flute\Core\Support\FluteRequest;
use Flute\Core\Template\Template;

/**
 * Service for handling installer views
 */
class InstallerView
{
    /**
     * @var Template
     */
    protected $template;

    /**
     * @var InstallerConfig
     */
    protected $installerConfig;

    protected array $components = [
        1 => 'installer.language',
        2 => 'installer.requirements',
        3 => 'installer.flute_key',
        4 => 'installer.database',
        5 => 'installer.admin',
        6 => 'installer.site_info',
        7 => 'installer.site_settings',
    ];

    /**
     * InstallerView constructor.
     */
    public function __construct(Template $template, InstallerConfig $installerConfig)
    {
        $this->template = $template;
        $this->installerConfig = $installerConfig;
    }

    /**
     * Render an installer view
     *
     * @return string
     */
    public function render(array $data = [], ?int $currentStep = null)
    {
        $step = $this->installerConfig->getCurrentStep();
        $totalSteps = $this->installerConfig->getTotalSteps();

        $defaultData = [
            'currentStep' => $currentStep,
            'step' => $step,
            'steps' => $this->components,
            'totalSteps' => $totalSteps,
            'progress' => ($step / $totalSteps) * 100,
            'params' => $this->installerConfig->getParams(),
        ];

        return view("installer::layout", array_merge($defaultData, $data));
    }

    /**
     * Render a step view
     *
     * @return string
     */
    public function renderStep(int $step, FluteRequest $request, array $data = [])
    {
        $currentStep = $this->installerConfig->getCurrentStep();

        if ($step > $currentStep && $step == $currentStep + 1) {
            $this->installerConfig->setCurrentStep($step);
            config()->save();
        }

        return $this->render(array_merge([
            'request' => $request,
            'component' => $this->components[$step],
        ], $data), $step);
    }
}
