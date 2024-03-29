<?php

namespace Flute\Core\Theme\Events;

use Symfony\Contracts\EventDispatcher\Event;

class ThemeDeactivated extends Event
{
    public const NAME = 'theme.deactivated';

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