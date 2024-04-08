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
        $this->clearCache();

        return $installController->success();
    }

    protected function createLogs()
    {
        @file_put_contents(BASE_PATH . "storage/logs/flute.log", '');
        @file_put_contents(BASE_PATH . "storage/logs/modules.log", '');
        @file_put_contents(BASE_PATH . "storage/logs/templates.log", '');
        @file_put_contents(BASE_PATH . "storage/logs/database.log", '');
    }

    protected function clearCache()
    {
        $cssCachePath = BASE_PATH . '/public/assets/css/cache/*';
        $jsCachePath = BASE_PATH . '/public/assets/js/cache/*';

        try {
            $filesystem = fs();
            
            $filesystem->remove(glob($cssCachePath));
            $filesystem->remove(glob($jsCachePath));
        } catch (Exception $e) {
            //
        }
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

        fs()->updateConfig(BASE_PATH . 'config/lang.php', $lang);
    }
}