<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class IPController extends AbstractController
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
}