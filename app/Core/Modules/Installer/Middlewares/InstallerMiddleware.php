<?php

namespace Flute\Core\Modules\Installer\Middlewares;

use Flute\Core\Modules\Installer\Services\InstallerConfig;
use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;

class InstallerMiddleware extends BaseMiddleware
{
    public function handle(FluteRequest $request, \Closure $next, ...$args): \Symfony\Component\HttpFoundation\Response
    {
        $id = (int) $request->input('id');

        $installerConfig = app(InstallerConfig::class);

        abort_if(!(($id < 0 || $id > $installerConfig->getTotalSteps()) || (bool) $installerConfig->isInstalled() === true), 404);

        if ($id > $installerConfig->getCurrentStep()) {
            return $this->error()->forbidden(__("install.last_step_required"));
        }

        return $next($request, $id);
    }
}
