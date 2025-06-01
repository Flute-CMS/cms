<?php

namespace Flute\Admin\Platform\Contracts;

use Flute\Admin\Platform\Support\Color;

interface Cardable
{
    public function title(): string;

    public function description(): string;

    /**
     * @return string
     */
    public function image(): ?string;

    /**
     * @return Color
     */
    public function color(): ?Color;
}
