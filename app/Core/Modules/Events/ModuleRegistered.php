<?php

namespace Flute\Core\Modules\Events;

use Flute\Core\Modules\ModuleInformation;
use Symfony\Contracts\EventDispatcher\Event;

class ModuleRegistered extends Event
{
    public const NAME = 'module.registered';

    protected string $moduleName;
    protected ModuleInformation $moduleInformation;

    public function __construct(string $moduleName, ModuleInformation $moduleInformation)
    {
        $this->moduleName = $moduleName;
        $this->moduleInformation = $moduleInformation;
    }

    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    public function getModuleInformation(): ModuleInformation
    {
        return $this->moduleInformation;
    }
}