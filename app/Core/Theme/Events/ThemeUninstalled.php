<?php

namespace Flute\Core\Theme\Events;

use Symfony\Contracts\EventDispatcher\Event;

class ThemeUninstalled extends Event
{
    public const NAME = 'theme.uninstalled';

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