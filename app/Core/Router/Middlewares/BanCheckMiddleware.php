<?php

namespace Flute\Core\Router\Middlewares;

use Closure;
use Flute\Core\Database\Entities\UserDevice;
use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;

class BanCheckMiddleware extends BaseMiddleware
{
    protected const CACHE_TIME = 60;

    public function handle(FluteRequest $request, Closure $next, ...$args): \Symfony\Component\HttpFoundation\Response
    {
        if (!is_installed()) {
            return $next($request);
        }

        if ($this->shouldBlockUser($request)) {
            $reason = $this->getBlockReason($request);

            return $this->error()->forbidden(__('def.you_are_blocked', [
                ":reason" => $reason,
            ]));
        }

        return $next($request);
    }

    protected function shouldBlockUser(FluteRequest $request): bool
    {
        return !user()->can('admin.boss') && (
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
            $blockInfo = $this->getIpBlockInfo($ipAddress);
            if ($blockInfo) {
                return $blockInfo['reason'];
            }
        }

        return __('def.unknown_reason');
    }

    protected function checkIpBlocks(string $ipAddress): bool
    {
        if (!$ipAddress) {
            return false;
        }

        $cacheKey = 'flute.ip_blocked.' . md5($ipAddress);

        return cache()->callback($cacheKey, static function () use ($ipAddress) {
            $users = UserDevice::query()
                ->where('ip', $ipAddress)
                ->load('user')
                ->load('user.blocksReceived')
                ->fetchAll();

            foreach ($users as $userDevice) {
                if ($userDevice->user->isBlocked()) {
                    return true;
                }
            }

            return false;
        }, self::CACHE_TIME);
    }

    protected function getIpBlockInfo(string $ipAddress): ?array
    {
        $cacheKey = 'flute.ip_block_info.' . md5($ipAddress);

        return cache()->callback($cacheKey, static function () use ($ipAddress) {
            $users = UserDevice::query()
                ->where('ip', $ipAddress)
                ->load('user')
                ->load('user.blocksReceived')
                ->fetchAll();

            foreach ($users as $userDevice) {
                if ($userDevice->user->isBlocked()) {
                    return $userDevice->user->getBlockInfo();
                }
            }

            return null;
        }, self::CACHE_TIME);
    }
}
