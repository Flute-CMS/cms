<?php

namespace Flute\Admin\Platform\Components\Cells;

use Closure;
use Illuminate\View\Component;

class Number extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        protected float $value,
        protected int $decimals = 0,
        protected ?string $decimal_separator = '.',
        protected ?string $thousands_separator = ','
    ) {
    }

    /**
     * Get the view/contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|Closure|string
     */
    public function render()
    {
        return number_format($this->value, $this->decimals, $this->decimal_separator, $this->thousands_separator);
    }
}
