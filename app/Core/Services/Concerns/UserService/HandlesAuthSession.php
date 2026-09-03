<?php

namespace Flute\Core\Services\Concerns\UserService;

use DateTimeImmutable;
use Throwable;

trait HandlesAuthSession
{
    public function initializeBySession(): void
    {
        if (!is_installed()) {
            return;
        }

        $userId = session()->get('user_id');

        if ($userId) {
            if (!is_int($userId) || $userId <= 0) {
                $this->sessionExpired();

                return;
            }

            $this->currentUser = $this->get($userId, false, ['roles', 'roles.permissions', 'blocksReceived']);

            if (!$this->currentUser) {
                $this->sessionExpired();
            }
        }
    }

    public function authSession(bool $force = false): void
    {
        if ($this->triedToLogin && !$force) {
            return;
        }

        if (!is_installed()) {
            return;
        }

        $request = request();
        if (!$request->hasAuthenticationCookie() && empty($this->userToken)) {
            $this->triedToLogin = true;

            return;
        }

        if ($request->hasSessionCookie() && session()->has('user_id')) {
            $this->initializeBySession();
        } elseif ($this->userToken) {
            $this->initializeByToken();
        }

        if ($this->currentUser) {
            $this->updateLastLogged();
        }

        $this->triedToLogin = true;
    }

    public function updateLastLogged(): void
    {
        if (!$this->currentUser || $this->currentUser->isOnline()) {
            return;
        }

        try {
            $this->currentUser->last_logged = new DateTimeImmutable();
            transaction($this->currentUser)->run();
        } catch (Throwable $e) {
            logs()->error($e);
        }
    }

    protected function initializeByToken(): void
    {
        $t0 = microtime(true);
        if (!$this->userToken || !is_installed()) {
            return;
        }

        try {
            $tokenInfo = $this->authService->getInfoFromToken($this->userToken);

            if (empty($tokenInfo->user)) {
                logs()->warning('auth.token.no_user_for_token');
                $this->sessionExpired();

                return;
            }

            $securityTokenEnabled = (bool) config('auth.security_token');

            if ($securityTokenEnabled) {
                $currentDeviceDetails = $this->userDevice->getUserAgent();

                if ($currentDeviceDetails !== $tokenInfo->userDevice->deviceDetails) {
                    logs()->warning('auth.token.device_mismatch');
                    $this->sessionExpired();

                    return;
                }
            }

            if (config('auth.check_ip')) {
                $clientIp = request()->getClientIp();
                if ($tokenInfo->userDevice->ip !== $clientIp) {
                    logs()->warning('auth.token.ip_mismatch', [
                        'expected' => $tokenInfo->userDevice->ip,
                        'actual' => $clientIp,
                    ]);
                    $this->sessionExpired();

                    return;
                }
            }

            $this->currentUser = $this->get(
                (int) $tokenInfo->user->id,
                false,
                ['roles', 'roles.permissions', 'blocksReceived'],
            );

            if (!$this->currentUser) {
                $this->sessionExpired();

                return;
            }

            session()->set('user_id', $tokenInfo->user->id);

            $dt = (int) round(( microtime(true) - $t0 ) * 1000);
            if ($dt >= 20) {
                logs()->info('auth.token.initialize_success', ['ms' => $dt, 'user_id' => (int) $tokenInfo->user->id]);
            } else {
                logs()->debug('auth.token.initialize_success', ['ms' => $dt, 'user_id' => (int) $tokenInfo->user->id]);
            }
        } catch (Throwable $e) {
            logs()->warning('auth.token.initialize_failed', ['reason' => $e->getMessage()]);
            $this->sessionExpired();
        }
    }

    protected function sessionExpired(): void
    {
        session()->clear();
        session()->invalidate();

        $this->currentUser = null;
        $this->permissionsCache = null;
        $this->rolesCache = null;
        $this->highestPriority = null;
        $this->triedToLogin = true;
    }
}
