<?php

namespace Flute\Core\Modules\Contracts;

use Flute\Core\Modules\ModuleInformation;

interface ModuleActionInterface
{
    public function action(ModuleInformation &$moduleInformation) : bool;
}