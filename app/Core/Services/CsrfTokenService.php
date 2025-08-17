<?php

namespace Flute\Core\Services;

use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfTokenService
{
    private CsrfTokenManagerInterface $csrfTokenManager;
    private string $defaultTokenId = 'flute_csrf';

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * Генерирует или получает существующий CSRF-токен.
     *
     * @return string
     */
    public function getToken(string $tokenId = null): string
    {
        return $this->csrfTokenManager->getToken($tokenId ?: $this->defaultTokenId)->getValue();
    }

    /**
     * Проверяет валидность переданного CSRF-токена.
     *
     * @param string $token
     * @return bool
     */
    public function validateToken(string $token): bool
    {
        $csrfToken = new CsrfToken($this->defaultTokenId, $token);

        return $this->csrfTokenManager->isTokenValid($csrfToken);
    }
}
