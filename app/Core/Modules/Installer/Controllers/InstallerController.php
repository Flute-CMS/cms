<?php

namespace Flute\Core\Modules\Installer\Controllers;

use Flute\Core\Modules\Installer\Controllers\Concerns\HandlesAccountSiteStep;
use Flute\Core\Modules\Installer\Controllers\Concerns\HandlesCustomizeStep;
use Flute\Core\Modules\Installer\Controllers\Concerns\HandlesDatabaseStep;
use Flute\Core\Modules\Installer\Controllers\Concerns\HandlesInstallerShared;
use Flute\Core\Modules\Installer\Controllers\Concerns\HandlesLaunchStep;
use Flute\Core\Modules\Installer\Controllers\Concerns\HandlesWelcomeStep;
use Flute\Core\Modules\Installer\Services\InstallerConfig;
use Flute\Core\Modules\Installer\Services\InstallerView;
use Flute\Core\Modules\Installer\Services\SystemRequirements;
use Flute\Core\Services\ConfigurationService;
use Flute\Core\Support\BaseController;

class InstallerController extends BaseController
{
    use HandlesAccountSiteStep;
    use HandlesCustomizeStep;
    use HandlesDatabaseStep;
    use HandlesInstallerShared;
    use HandlesLaunchStep;
    use HandlesWelcomeStep;

    protected InstallerView $installerView;

    protected InstallerConfig $installerConfig;

    protected ConfigurationService $configService;

    protected SystemRequirements $systemRequirements;

    public function __construct(
        InstallerView $installerView,
        InstallerConfig $installerConfig,
        ConfigurationService $configService,
        SystemRequirements $systemRequirements,
    ) {
        $this->installerView = $installerView;
        $this->installerConfig = $installerConfig;
        $this->configService = $configService;
        $this->systemRequirements = $systemRequirements;
    }
}
