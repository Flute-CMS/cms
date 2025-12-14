<?php

namespace Flute\Core\Theme\Events;

use Symfony\Contracts\EventDispatcher\Event;

class ThemeColorsUpdated extends Event
{
    public const NAME = 'theme.colors.updated';

    public string $themeName;

    public array $updatedColors;

    /**
     * Constructor for ThemeColorsUpdated event.
     */
    public function __construct(string $themeName, array $updatedColors)
    {
        $this->themeName = $themeName;
        $this->updatedColors = $updatedColors;
    }
}
