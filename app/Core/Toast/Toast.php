<?php

namespace Flute\Core\Toast;

use JsonSerializable;

class Toast implements JsonSerializable
{
    private ToastType $type;
    private string $message;
    private ?int $duration = null;
    private ?bool $dismissible = null;
    private ?array $position = null;
    private ?bool $ripple = null;
    private string|array|null $icon = null;
    private ?string $className = null;
    private array $events = [];

    public function __construct(string $message, ToastType $type = ToastType::INFO)
    {
        $this->message = $message;
        $this->type = $type;
    }

    public static function make(string $message, ToastType $type = ToastType::INFO): self
    {
        return new self($message, $type);
    }

    public function withDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function dismissible(bool $dismissible = true): self
    {
        $this->dismissible = $dismissible;

        return $this;
    }

    public function withPosition(string $x, string $y): self
    {
        $this->position = ['x' => $x, 'y' => $y];

        return $this;
    }

    public function withRipple(bool $ripple = true): self
    {
        $this->ripple = $ripple;

        return $this;
    }

    public function withIcon(string|array|null $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function withClassName(string $className): self
    {
        $this->className = $className;

        return $this;
    }

    /**
     * Добавляет обработчик события для тоста.
     *
     * @param string $eventName Название события ('click', 'dismiss' и т.д.)
     * @param string $callback JavaScript функция-обработчик события.
     * @return self
     */
    public function on(string $eventName, string $callback): self
    {
        $this->events[$eventName] = $callback;

        return $this;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'type' => $this->type->value,
            'message' => $this->message,
        ];

        if ($this->duration !== null) {
            $data['duration'] = $this->duration;
        }

        if ($this->dismissible !== null) {
            $data['dismissible'] = $this->dismissible;
        }

        if ($this->position !== null) {
            $data['position'] = $this->position;
        }

        if ($this->ripple !== null) {
            $data['ripple'] = $this->ripple;
        }

        if ($this->icon !== null) {
            $data['icon'] = $this->icon;
        }

        if ($this->className !== null) {
            $data['className'] = $this->className;
        }

        if (!empty($this->events)) {
            $data['events'] = $this->events;
        }

        return $data;
    }
}
