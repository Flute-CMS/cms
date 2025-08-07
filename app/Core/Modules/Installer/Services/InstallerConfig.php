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
     *
     * @param ConfigurationService $configService
     */
    public function __construct(ConfigurationService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Check if the application is installed
     *
     * @return bool
     */
    public function isInstalled(): bool
    {
        return $this->configService->get('installer.finished', false);
    }

    /**
     * Get the current installation step
     *
     * @return int
     */
    public function getCurrentStep(): int
    {
        return $this->configService->get('installer.step', 0);
    }

    /**
     * Set the current installation step
     *
     * @param int $step
     * @return void
     */
    public function setCurrentStep(int $step): void
    {
        $this->configService->set('installer.step', $step);
    }

    /**
     * Get the total number of installation steps
     *
     * @return int
     */
    public function getTotalSteps(): int
    {
        return $this->configService->get('installer.step_total', 7);
    }

    /**
     * Mark the installation as complete
     *
     * @return void
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
     * @param string|null $key
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
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setParam(string $key, $value): void
    {
        $this->configService->set("installer.params.{$key}", $value);
        $this->configService->save();
    }

    /**
     * Set multiple installation parameters
     *
     * @param array $params
     * @return void
     */
    public function setParams(array $params): void
    {
        foreach ($params as $key => $value) {
            $this->configService->set("installer.params.{$key}", $value);
        }

        $this->configService->save();
    }
}
