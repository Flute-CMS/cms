<?php

namespace Flute\Core\Rcon;

use RuntimeException;

/**
 * Неверный RCON-пароль. Сервер ответил — значит он доступен,
 * и такую ошибку нельзя считать сетевым сбоем для circuit breaker.
 */
class RconAuthException extends RuntimeException
{
}
