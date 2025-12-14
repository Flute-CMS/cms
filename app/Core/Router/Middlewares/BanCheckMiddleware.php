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
        if (user()->can('admin.boss')) {
            return false;
        }

        if (user()->isLoggedIn() && user()->isBlocked()) {
            return true;
        }

        $ipAddress = $request->getClientIp();
        if (!$ipAddress) {
            return false;
        }

        $blockInfo = $this->resolveIpBlock($ipAddress);

        return $blockInfo['blocked'];
    }

    protected function getBlockReason(FluteRequest $request): string
    {
        if (user()->isLoggedIn() && user()->isBlocked()) {
            return user()->getCurrentUser()->getBlockInfo()['reason'];
        }

        $ipAddress = $request->getClientIp();
        if ($ipAddress) {
            $blockInfo = $this->resolveIpBlock($ipAddress);
            if ($blockInfo['blocked']) {
                return $blockInfo['reason'] ?? __('def.unknown_reason');
            }
        }

        return __('def.unknown_reason');
    }

    /**
     * Single cached lookup for IP ban status and reason.
     */
    protected function resolveIpBlock(string $ipAddress): array
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
                    $info = $userDevice->user->getBlockInfo();

                    return [
                        'blocked' => true,
                        'reason' => $info['reason'] ?? null,
                    ];
                }
            }

            return [
                'blocked' => false,
                'reason' => null,
            ];
        }, self::CACHE_TIME);
    }
}
