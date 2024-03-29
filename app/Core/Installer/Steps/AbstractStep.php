<?php

namespace Flute\Core\Installer\Steps;

use Flute\Core\Http\Controllers\APIInstallerController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Чисто абстракт для одного метода..
 */
abstract class AbstractStep
{
    /**
     * Install function for current step
     *
     * @param FluteRequest $request Request
     * @param APIInstallerController $installController
     * @return Response
     */
    abstract public function install(FluteRequest $request, APIInstallerController $installController) : Response;
}