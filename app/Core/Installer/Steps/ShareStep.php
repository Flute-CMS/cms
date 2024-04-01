<?php

namespace Flute\Core\Installer\Steps;

use Exception;
use Flute\Core\Http\Controllers\APIInstallerController;
use Symfony\Component\HttpFoundation\Response;

class ShareStep extends AbstractStep
{
    protected $lang;
    protected $share;
    protected $key;
    protected $timezone;

    /**
     * @throws Exception
     */
    public function install(\Flute\Core\Support\FluteRequest $request, APIInstallerController $installController): Response
    {
        $this->share = $request->input('share') === 'on';
        $this->lang = config('installer.params.lang');
        $this->key = config('installer.params.key');
        $this->timezone = config('installer.params.timezone');

        $installController->setFinished(true);

        $this->updateConfig();
        $this->createLogs();

        return $installController->success();
    }

    protected function createLogs()
    {
        @file_put_contents(BASE_PATH . "storage/logs/flute.log", '');
        @file_put_contents(BASE_PATH . "storage/logs/modules.log", '');
        @file_put_contents(BASE_PATH . "storage/logs/templates.log", '');
        @file_put_contents(BASE_PATH . "storage/logs/database.log", '');
    }

    /**
     * @throws Exception
     */
    protected function updateConfig(): void
    {
        $app = config('app');

        $app['key'] = $this->key;
        $app['timezone'] = $this->timezone;
        $app['share'] = (bool) $this->share;

        fs()->updateConfig(BASE_PATH . 'config/app.php', $app);

        $lang = config('lang');
        $lang['locale'] = $this->lang;
        $lang['available'] = [
            0 => 'en',
            1 => 'ru',
            2 => 'pl',
            3 => 'uk',
            4 => 'de',
            5 => 'zh',
            6 => 'fr',
            7 => 'es',
            8 => 'uz',
        ];

        fs()->updateConfig(BASE_PATH . 'config/lang.php', $lang);
    }
}