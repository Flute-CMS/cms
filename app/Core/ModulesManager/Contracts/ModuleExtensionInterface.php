<?php

namespace Flute\Core\ModulesManager\Contracts;

/**
 * The ModuleExtension interface defines the contract for a module extension in the context of a Module Service Provider.
 * A module extension is responsible for registering additional functionality or services provided by a module.
 */
interface ModuleExtensionInterface
{
    /**
     * Register the module extension.
     *
     * This method should be implemented to register any additional functionality or services provided by the module.
     * It will be called by the Module Service Provider during the registration phase of the module.
     */
    public function register(): void;
}
