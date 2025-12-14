<?php

namespace Flute\Admin\Platform\Traits;

trait IsVisible
{
    /**
     * Serves as a presentation indicator.
     * If the value is false, the template will not be output.
     *
     * @var bool
     */
    private $display = true;

    /**
     * @return $this
     */
    public function setVisible(bool $value): self
    {
        $this->display = $value;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->display;
    }
}
