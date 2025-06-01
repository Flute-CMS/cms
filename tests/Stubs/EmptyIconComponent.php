<?php

namespace Tests\Stubs;

use Illuminate\View\Component;

class EmptyIconComponent extends Component
{
    public function render()
    {
        return <<<'BLADE'
            <!-- no icon -->
            BLADE;
    }
}
