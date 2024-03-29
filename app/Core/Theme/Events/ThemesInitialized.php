<?php

namespace Flute\Core\Theme\Events;

use Flute\Core\Theme\ThemeManager;
use Symfony\Contracts\EventDispatcher\Event;

class ThemesInitialized extends Event
{
    public const NAME = 'themes.initialized';

    private ThemeManager $themeManager;

    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    public function getThemeManager(): ThemeManager
    {
        return $this->themeManager;
    }

    public function setThemeManager(ThemeManager $themeManager): void
    {
        $this->themeManager = $themeManager;
    }
}
