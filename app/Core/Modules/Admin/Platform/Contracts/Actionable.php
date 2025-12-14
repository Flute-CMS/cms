<?php

namespace Flute\Admin\Platform\Contracts;

use Flute\Admin\Platform\Repository;

interface Actionable
{
    /**
     * @return mixed
     */
    public function build(Repository $repository);
}
