<?php

namespace Flute\Admin\Platform\Components\Cells;

use Closure;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class Currency extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        protected ?float $value,
        protected int $decimals = 2,
        protected ?string $decimal_separator = '.',
        protected ?string $thousands_separator = ',',
        protected ?string $before = '',
        protected ?string $after = '',
    ) {
    }

    /**
     * Get the view/contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|Closure|string
     */
    public function render()
    {
        $value = number_format($this->value, $this->decimals, $this->decimal_separator, $this->thousands_separator);

        return Str::of($value)
            ->prepend($this->before.' ')
            ->append(' '.$this->after)
            ->trim()
            ->toString();
    }
}
