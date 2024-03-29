<?php

namespace Flute\Core\Installer\Steps;

use Exception;
use Flute\Core\Http\Controllers\APIInstallerController;
use Symfony\Component\HttpFoundation\Response;

class ReqsStep extends AbstractStep
{
    /**
     * @throws Exception
     */
    public function install(\Flute\Core\Support\FluteRequest $request, APIInstallerController $installController) : Response
    {
        $this->updateStep($installController);

        return $installController->success();
    }

    /**
     * @throws Exception
     */
    protected function updateStep(APIInstallerController $installController) : void
    {
        $config = $installController->defaultConfig;
        $config['step'] = $config['step'] > 2 ? $config["step"] : $config['step'] + 1;
        $installController->fileSystemService->updateConfig($installController::CONFIG_PATH, $config);
    }
}