<?php

namespace Flute\Core\Installer\Steps;

use Flute\Core\Http\Controllers\APIInstallerController;
use Symfony\Component\HttpFoundation\Response;

class TipsStep extends AbstractStep
{
    protected $tips;

    public function install(\Flute\Core\Support\FluteRequest $request, APIInstallerController $installController) : Response
    {
        $this->tips   = $request->get('tips') === 'on';

        $this->updateConfig();

        $installController->updateConfigStep('tips', $this->tips);

        return $installController->success();
    }

    protected function updateConfig(): void
    {
        $app = config('app');

        $app['tips']  = (bool) $this->tips;

        fs()->updateConfig(BASE_PATH . 'config/app.php', $app);
    }
}