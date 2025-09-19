<?php

namespace Flute\Admin\Platform\Components\Cells;

use Closure;
use DateTimeZone;
use Illuminate\Support\Carbon;
use Illuminate\View\Component;

class DateTime extends Component
{
    /**
     * Create a new component instance.
     *
     * @param float                     $value
     */
    public function __construct(
        protected mixed $value,
        protected string $format = 'd.m.Y H:i',
        protected DateTimeZone|null|string $tz = null,
    ) {
    }

    /**
     * Get the view/contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|Closure|string
     */
    public function render()
    {
        return Carbon::parse($this->value, $this->tz)->translatedFormat($this->format);
    }
}
