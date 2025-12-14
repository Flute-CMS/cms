<?php

namespace Flute\Core\Support;

use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Database\Entities\NavbarItem;
use Flute\Core\ModulesManager\ModuleInformation;

abstract class AbstractModuleInstaller
{
    protected ?string $key = null;

    public function __construct(?string $key = null)
    {
        $this->key = $key;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getNavItem(): ?NavbarItem
    {
        return null;
    }

    abstract public function install(ModuleInformation &$module): bool;

    abstract public function uninstall(ModuleInformation &$module): bool;

    protected function importMigrations()
    {
        $directory = sprintf('app/Modules/%s/database/migrations', $this->getKey());

        app(DatabaseConnection::class)->runMigrations($directory);
    }
}
