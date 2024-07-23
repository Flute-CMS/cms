<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Admin\Services\Config\AppConfigService;
use Flute\Core\Admin\Services\Config\AuthConfigService;
use Flute\Core\Admin\Services\Config\DatabaseConfigService;
use Flute\Core\Admin\Services\Config\LangConfigService;
use Flute\Core\Admin\Services\Config\LkConfigService;
use Flute\Core\Admin\Services\Config\MailConfigService;
use Flute\Core\Admin\Services\Config\ProfileConfigService;
use Flute\Core\Admin\Services\LogService;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class MainSettingsController extends AbstractController
{
    private $configServices;

    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.system');

        $this->initConfigServices();
    }

    public function index(FluteRequest $request, string $tab)
    {
        if (!isset($this->configServices[$tab])) {
            return $this->error('Invalid settings');
        }

        $params = $request->input();

        $params['files'] = $request->files;

        $result = $this->configServices[$tab]->updateConfig($params);

        $this->clearContainer();

        return $result;
    }

    public function createLog(FluteRequest $fluteRequest)
    {
        $logService = app(LogService::class);

        $logFilePath = $logService->generateLogFile();
        return $logService->downloadLogFile($logFilePath);
    }

    protected function clearContainer(): void
    {
        try {
            fs()->remove(BASE_PATH . 'storage/app/cache/CompiledContainer.php');
        } catch (\Exception $e) {
            logs()->warning($e);
        }
    }

    protected function initConfigServices(): void
    {
        $this->configServices = [
            'app' => app(AppConfigService::class),
            'additional' => app(AppConfigService::class),
            'auth' => app(AuthConfigService::class),
            'database' => app(DatabaseConfigService::class),
            'lang' => app(LangConfigService::class),
            'mail' => app(MailConfigService::class),
            'profile' => app(ProfileConfigService::class),
            'lk' => app(LkConfigService::class),
        ];
    }
}