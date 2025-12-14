<?php

namespace Flute\Core\Toast;

use Flute\Core\Services\ToastService;
use LogicException;

class ToastBuilder
{
    private ToastService $toastService;

    private ?Toast $toast = null;

    public function __construct(ToastService $toastService)
    {
        $this->toastService = $toastService;
    }

    public function success(string $message): self
    {
        $this->toast = new Toast($message, ToastType::SUCCESS);

        return $this;
    }

    public function error(string $message): self
    {
        $this->toast = new Toast($message, ToastType::ERROR);

        return $this;
    }

    public function warning(string $message): self
    {
        $this->toast = new Toast($message, ToastType::WARNING);

        return $this;
    }

    public function info(string $message): self
    {
        $this->toast = new Toast($message, ToastType::INFO);

        return $this;
    }

    public function custom(string $message, ToastType $type): self
    {
        $this->toast = new Toast($message, $type);

        return $this;
    }

    public function withDuration(int $duration): self
    {
        $this->ensureToast();
        $this->toast->withDuration($duration);

        return $this;
    }

    public function dismissible(bool $dismissible = true): self
    {
        $this->ensureToast();
        $this->toast->dismissible($dismissible);

        return $this;
    }

    public function withPosition(string $x, string $y): self
    {
        $this->ensureToast();
        $this->toast->withPosition($x, $y);

        return $this;
    }

    public function withRipple(bool $ripple = true): self
    {
        $this->ensureToast();
        $this->toast->withRipple($ripple);

        return $this;
    }

    public function withIcon(string|array|null $icon): self
    {
        $this->ensureToast();
        $this->toast->withIcon($icon);

        return $this;
    }

    public function withClassName(string $className): self
    {
        $this->ensureToast();
        $this->toast->withClassName($className);

        return $this;
    }

    public function on(string $eventName, string $callback): self
    {
        $this->ensureToast();
        $this->toast->on($eventName, $callback);

        return $this;
    }

    /**
     * Добавляет настроенный тост в ToastService.
     */
    public function push(): void
    {
        $this->ensureToast();
        $this->toastService->addToast($this->toast);
        $this->toast = null; // Reset after pushing
    }

    /**
     * Проверяет, инициализирован ли тост.
     *
     * @throws LogicException
     */
    private function ensureToast(): void
    {
        if ($this->toast === null) {
            throw new LogicException("Toast type must be set before setting options.");
        }
    }
}
