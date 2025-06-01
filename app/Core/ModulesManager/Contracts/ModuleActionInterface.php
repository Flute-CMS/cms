<?php

namespace Flute\Core\ModulesManager\Contracts;

use Flute\Core\ModulesManager\ModuleInformation;

interface ModuleActionInterface
{
    public function action(ModuleInformation &$moduleInformation) : bool;
}