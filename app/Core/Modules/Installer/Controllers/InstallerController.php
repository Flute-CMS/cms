<?php

namespace Flute\Core\Modules\Installer\Controllers;

use Flute\Core\Modules\Installer\Services\InstallerConfig;
use Flute\Core\Modules\Installer\Services\InstallerView;
use Flute\Core\Router\Annotations\Route;
use Flute\Core\Services\ConfigurationService;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

/**
 * Controller for handling installer requests
 */
class InstallerController extends BaseController
{
    /**
     * @var InstallerView
     */
    protected $installerView;

    /**
     * @var InstallerConfig
     */
    protected $installerConfig;

    /**
     * @var ConfigurationService
     */
    protected $configService;

    /**
     * InstallerController constructor.
     */
    public function __construct(
        InstallerView $installerView,
        InstallerConfig $installerConfig,
        ConfigurationService $configService,
    ) {
        $this->installerView = $installerView;
        $this->installerConfig = $installerConfig;
        $this->configService = $configService;
    }

    /**
     * Display the installer welcome page
     *
     * @return string
     */
    #[Route('/install', name: 'installer.welcome', methods: ['GET'])]
    public function welcome()
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        return $this->installerView->render([
            'component' => 'installer.welcome',
            'preferredLanguage' => $this->getPreferredLanguage(),
        ]);
    }

    /**
     * Display the installer step
     *
     * @return string
     */
    #[Route('/install/{id}', name: 'installer.step', methods: ['GET', 'POST'])]
    public function index(FluteRequest $request, int $id)
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        if ($id < 1 || $id > $this->installerConfig->getTotalSteps()) {
            return response()->error(404, 'Installer step not found');
        }

        return $this->installerView->renderStep($id, $request);
    }

    /**
     * Get the preferred language
     *
     * @return string
     */
    protected function getPreferredLanguage()
    {
        return translation()->getPreferredLanguage();
    }
}
