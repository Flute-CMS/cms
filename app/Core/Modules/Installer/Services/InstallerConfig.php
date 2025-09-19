<?php

namespace Flute\Core\Modules\Installer\Services;

use Flute\Core\Services\ConfigurationService;

/**
 * Service for managing installer configuration
 */
class InstallerConfig
{
    /**
     * @var ConfigurationService
     */
    protected $configService;

    /**
     * InstallerConfig constructor.
     */
    public function __construct(ConfigurationService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Check if the application is installed
     */
    public function isInstalled(): bool
    {
        return $this->configService->get('installer.finished', false);
    }

    /**
     * Get the current installation step
     */
    public function getCurrentStep(): int
    {
        return $this->configService->get('installer.step', 0);
    }

    /**
     * Set the current installation step
     */
    public function setCurrentStep(int $step): void
    {
        $this->configService->set('installer.step', $step);
    }

    /**
     * Get the total number of installation steps
     */
    public function getTotalSteps(): int
    {
        return $this->configService->get('installer.step_total', 7);
    }

    /**
     * Mark the installation as complete
     */
    public function markAsInstalled(): void
    {
        $config = config('installer');
        $config['finished'] = true;

        config()->set('installer', $config);
        config()->save();
    }

    /**
     * Get installation parameters
     *
     * @param mixed $default
     * @return mixed
     */
    public function getParams(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->configService->get('installer.params', []);
        }

        return $this->configService->get("installer.params.{$key}", $default);
    }

    /**
     * Set installation parameter
     *
     * @param mixed $value
     */
    public function setParam(string $key, $value): void
    {
        $this->configService->set("installer.params.{$key}", $value);
        $this->configService->save();
    }

    /**
     * Set multiple installation parameters
     */
    public function setParams(array $params): void
    {
        foreach ($params as $key => $value) {
            $this->configService->set("installer.params.{$key}", $value);
        }

        $this->configService->save();
    }
}
