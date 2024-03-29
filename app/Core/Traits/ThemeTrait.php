<?php

namespace Flute\Core\Traits;

use Flute\Core\Template\Template;

trait ThemeTrait
{
    /**
     * @var string
     */
    public string $theme = "standard";

    /**
     * @var Template
     */
    protected Template $Template;

    public function setTheme(string $theme) : self
    {
        $this->theme = $theme;
        $this->bind('theme', $theme);

        return $this;
    }

    public function getTheme() : string
    {
        return $this->theme;
    }
}