<?php

namespace Flute\Core\Router\Middlewares;

use Flute\Core\Database\Entities\UserDevice;
use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;

class BanCheckMiddleware extends BaseMiddleware
{
    public function handle(FluteRequest $request, \Closure $next, ...$args) : \Symfony\Component\HttpFoundation\Response
    {
        if (!is_installed())
            return $next($request);

        if ($this->shouldBlockUser($request)) {
            $reason = $this->getBlockReason($request);
            return $this->error()->forbidden(__('def.you_are_blocked', [
                ":reason" => $reason
            ]));
        }

        return $next($request);
    }

    protected function shouldBlockUser(FluteRequest $request) : bool
    {
        return !user()->can('admin.boss') && (
            (user()->isLoggedIn() && user()->isBlocked()) ||
            $this->checkIpBlocks($request->getClientIp())
        );
    }

    protected function getBlockReason(FluteRequest $request) : string
    {
        if (user()->isLoggedIn() && user()->isBlocked()) {
            return user()->getCurrentUser()->getBlockInfo()['reason'];
        }

        $ipAddress = $request->getClientIp();
        if ($ipAddress) {
            $users = UserDevice::findAll(['ip' => $ipAddress]);

            foreach ($users as $userDevice) {
                if ($userDevice->user->isBlocked()) {
                    return $userDevice->user->getBlockInfo()['reason'];
                }
            }
        }

        return __('def.unknown_reason');
    }

    protected function checkIpBlocks(string $ipAddress) : bool
    {
        if ($ipAddress) {
            $users = UserDevice::findAll(['ip' => $ipAddress]);

            foreach ($users as $userDevice) {
                if ($userDevice->user->isBlocked()) {
                    return true;
                }
            }
        }

        return false;
    }
}
