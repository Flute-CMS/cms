<?php

namespace Flute\Admin\Platform\Actions;

use Flute\Admin\Platform\Repository;

class DropDownItem extends Button
{
    public function build(?Repository $repository = null): mixed
    {
        $this->baseClasses('btn dropdown-item');

        return parent::build($repository);
    }
}
