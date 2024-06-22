<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class HelperAdminController extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.system');
    }

    public function getIP(FluteRequest $fluteRequest)
    {
        return json([
            'ip' => $fluteRequest->getClientIp(),
        ]);
    }

    public function checkSteam(FluteRequest $fluteRequest)
    {
        $token = $fluteRequest->input('apiKey');

        if (!$token)
            return $this->error(__("admin.app.token_incorrect"));

        config()->set('app.steam_api', $token);

        try {

            $player = steam()->getUser(76561198295345385, true);

            if (empty($player)) {
                return $this->error(__("admin.app.token_incorrect"));
            }
        } catch (\Exception $e) {
            return $this->error(__("admin.app.token_incorrect"));
        }

        return $this->success();
    }
}