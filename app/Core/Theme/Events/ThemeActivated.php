<?php

namespace Flute\Core\Theme\Events;

use Symfony\Contracts\EventDispatcher\Event;

class ThemeActivated extends Event
{
    public const NAME = 'theme.activated';

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
