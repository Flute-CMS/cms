<?php

namespace Flute\Core\Theme\Events;

use Symfony\Contracts\EventDispatcher\Event;

class ThemeInstalled extends Event
{
    public const NAME = 'theme.installed';

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
