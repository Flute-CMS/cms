<?php

namespace Flute\Core\ModulesManager\Events;

use Flute\Core\ModulesManager\ModuleInformation;
use Symfony\Contracts\EventDispatcher\Event;

class ModuleUpdate extends Event
{
    public const NAME = 'module.update';

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