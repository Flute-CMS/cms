<?php

namespace Flute\Core\Theme\Events;

use Symfony\Contracts\EventDispatcher\Event;

class ThemeChangedEvent extends Event
{
    public const NAME = 'theme.Changed';

    protected string $theme;

    public function __construct(string $theme)
    {
        $this->theme = $theme;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }
}