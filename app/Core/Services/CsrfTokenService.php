<?php

namespace Flute\Core\Services;

class CsrfTokenService
{
    protected string $sessionKey = '_csrf_token';

    public function generateToken(): string
    {
        if (!$this->sessionHasToken()) {
            $this->setSessionToken(bin2hex(random_bytes(32)));
        }

        return $this->getSessionToken();
    }

    public function getToken(): string
    {
        return $this->getSessionToken() ?? $this->generateToken();
    }

    public function validateToken(): bool
    {
        $token = $this->getRequestToken();

        return $token ? hash_equals($this->getToken(), $token) : false;
    }

    protected function sessionHasToken(): bool
    {
        return session()->has($this->sessionKey);
    }

    protected function getSessionToken(): ?string
    {
        return session()->get($this->sessionKey);
    }

    protected function setSessionToken(string $token): void
    {
        session()->set($this->sessionKey, $token);
    }

    protected function getRequestToken(): ?string
    {
        return request()->input('x-csrf-token')
            ?? request()->headers->get('x-csrf-token')
            ?? request()->input('x_csrf_token')
            ?? request()->headers->get('x_csrf_token');
    }
}
