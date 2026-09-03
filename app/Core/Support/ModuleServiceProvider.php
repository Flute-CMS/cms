<?php

namespace Flute\Core\Support;

use Flute\Core\ModulesManager\Contracts\ModuleServiceProviderInterface;
use Flute\Core\Support\Concerns\HandlesModuleAssets;
use Flute\Core\Support\Concerns\HandlesModuleBootstrap;
use Flute\Core\Support\Concerns\HandlesModuleMeta;
use Flute\Core\Support\Concerns\HandlesModulePaths;
use Flute\Core\Support\Concerns\HandlesModuleResources;
use Flute\Core\Support\Concerns\HandlesModuleRoutes;

/**
 * Class ModuleServiceProvider
 *
 * Abstract base for module providers. Composed from Handles* traits.
 */
abstract class ModuleServiceProvider implements ModuleServiceProviderInterface
{
    use HandlesModuleAssets;
    use HandlesModuleBootstrap;
    use HandlesModuleMeta;
    use HandlesModulePaths;
    use HandlesModuleResources;
    use HandlesModuleRoutes;

    /**
     * @var array|string[]
     */
    public array $extensions = [];

    protected ?string $moduleName = '';

    protected ?string $namespace = '';

    protected array $listen = [];

    protected array $updateChannel = [];

    /**
     * @var int Default cache duration in seconds.
     */
    protected int $defaultCacheDuration = 3600;

    /**
     * @var array Runtime cache for directories
     */
    private array $directoryCache = [];

    /**
     * @var array Runtime cache for module paths
     */
    private array $modulePathCache = [];

    /**
     * @var array Track loaded resources to prevent duplicate loading
     */
    private array $loadedStatus = [];
}
