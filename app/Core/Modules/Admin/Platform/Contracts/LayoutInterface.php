<?php

namespace Flute\Admin\Platform\Contracts;

use Flute\Admin\Platform\Repository;

interface LayoutInterface
{
    public function build(Repository $repository);
}
