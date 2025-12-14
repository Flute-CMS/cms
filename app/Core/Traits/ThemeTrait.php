<?php

namespace Flute\Core\Traits;

use Flute\Core\Template\Template;

trait ThemeTrait
{
    /**
     */
    public string $theme = "standard";

    /**
     */
    protected Template $Template;

    public function setTheme(string $theme): self
    {
        $this->theme = $theme;
        $this->bind('theme', $theme);

        return $this;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }
}
