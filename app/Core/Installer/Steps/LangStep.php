<?php

namespace Flute\Core\Installer\Steps;

use DI\DependencyException;
use DI\NotFoundException;
use Flute\Core\Http\Controllers\APIInstallerController;
use Flute\Core\Services\EncryptService;
use Symfony\Component\HttpFoundation\Response;

class LangStep extends AbstractStep
{
    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function install(\Flute\Core\Support\FluteRequest $request, APIInstallerController $installController) : Response
    {
        $lang = (string) $request->input('lang');
        $timezone = (string) $request->input('timezone');

        if (!in_array($lang, config('lang.available'))) {
            return $installController->error($installController->trans('Несуществующий язык'));
        }
        
        $installController->updateConfigStep('timezone',  $timezone);
        $installController->updateConfigStep('lang', $lang);
        $installController->updateConfigStep('key', base64_encode(EncryptService::generateKey('aes-256-cbc')));
        
        app()->setLang($lang);

        return $installController->success();
    }
}