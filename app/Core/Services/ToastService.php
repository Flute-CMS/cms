<?php

namespace Flute\Core\Services;

use Flute\Core\Toast\Toast;
use Flute\Core\Toast\ToastBuilder;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ToastService
{
    private const TOASTS_KEY = '_flute_toasts';

    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Возвращает экземпляр ToastBuilder для создания тоста.
     *
     * @return ToastBuilder
     */
    public function toast(): ToastBuilder
    {
        return new ToastBuilder($this);
    }

    /**
     * Добавляет тост в сессию.
     *
     * @param Toast $toast
     * @return void
     */
    public function addToast(Toast $toast): void
    {
        $toasts = $this->session->get(self::TOASTS_KEY, []);
        $toasts[] = $toast;
        $this->session->set(self::TOASTS_KEY, $toasts);
    }

    /**
     * Получает и очищает все тосты из сессии.
     *
     * @return Toast[]
     */
    public function getToasts(): array
    {
        $toasts = $this->session->get(self::TOASTS_KEY, []);
        $this->session->remove(self::TOASTS_KEY);

        return $toasts;
    }
}
