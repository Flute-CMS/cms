<?php

namespace Flute\Core\Http\Middlewares;

use Flute\Core\Database\Entities\UserDevice;
use Flute\Core\Support\AbstractMiddleware;
use Flute\Core\Support\FluteRequest;

class BanCheckMiddleware extends AbstractMiddleware
{
    public function __invoke(FluteRequest $request, \Closure $next)
    {
        if( !is_installed() ) return $next($request);

        if ($this->shouldBlockUser($request)) {
            $reason = $this->getBlockReason($request);
            return $this->error(__('def.you_are_blocked', [
                ":reason" => $reason
            ]));
        }

        return $next($request);
    }

    protected function shouldBlockUser(FluteRequest $request): bool
    {
        return !user()->hasPermission('admin.boss') && (
            (user()->isLoggedIn() && user()->isBlocked()) ||
            $this->checkIpBlocks($request->getClientIp())
        );
    }

    protected function getBlockReason(FluteRequest $request): string
    {
        if (user()->isLoggedIn() && user()->isBlocked()) {
            return user()->getCurrentUser()->getBlockInfo()['reason'];
        }

        $ipAddress = $request->getClientIp();
        if ($ipAddress) {
            $userRepository = rep(UserDevice::class);
            $users = $userRepository->select()->where(['ip' => $ipAddress])->load([
                'user.blocksReceived'
            ])->fetchAll();

            foreach ($users as $userDevice) {
                if ($userDevice->user->isBlocked()) {
                    return $userDevice->user->getBlockInfo()['reason'];
                }
            }
        }

        return __('def.unknown_reason');
    }

    protected function checkIpBlocks(string $ipAddress): bool
    {
        if ($ipAddress) {
            $userRepository = rep(UserDevice::class);
            $users = $userRepository->select()->where(['ip' => $ipAddress])->load([
                'user.blocksReceived'
            ])->fetchAll();

            foreach ($users as $userDevice) {
                if ($userDevice->user->isBlocked()) {
                    return true;
                }
            }
        }

        return false;
    }
}
