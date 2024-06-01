<?php

namespace Flute\Core\Modules;

use Nette\Utils\Json;
use Nette\Utils\JsonException;

/**
 * Class ModuleInformation
 *
 * Represents the information of a module.
 */
class ModuleInformation
{
    public string $key;
    public string $name;
    public string $description;
    public ?string $installedVersion;
    public string $version;
    public array $authors;
    public string $url;
    public array $providers;
    public array $dependencies;
    public ?\DateTimeImmutable $created_at = null;
    public string $status = ModuleManager::NOTINSTALLED;

    /**
     * ModuleInformation constructor.
     *
     * Initializes class properties and loads module information from json file.
     *
     * @param string $moduleJSON JSON string with module information.
     * @param string $key Key to identify the module.
     * @throws JsonException
     */
    public function __construct( string $moduleJSON, string $key )
    {
        $this->key        = $key;
        
        $this->loadModuleJson($moduleJSON);
    }

    /**
     * Loads the module information from the provided JSON string.
     *
     * @param string $moduleJSON JSON string with module information.
     * @throws JsonException
     */
    protected function loadModuleJson( string $moduleJSON ) : void
    {
        $file = Json::decode($moduleJSON);

        $this->name           = $file->name;
        $this->description    = $file->description ?? '';
        $this->version        = $file->version ?? '1.0.0';
        $this->authors        = $file->authors ?? [];
        $this->url            = $file->url ?? '';
        $this->providers      = $file->providers;
        $this->dependencies   = (array) $file->dependencies ?? [];
    }
}