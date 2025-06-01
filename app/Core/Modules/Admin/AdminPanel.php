<?php

namespace Flute\Admin;

use Flute\Admin\Contracts\AdminPackageInterface;
use Flute\Core\Router\Router;
use Flute\Core\Traits\MacroableTrait;

/**
 * Class AdminPanel
 *
 * Manages the registration and initialization of admin packages.
 */
class AdminPanel
{
    use MacroableTrait;

    /**
     * @var AdminPackageFactory
     */
    protected AdminPackageFactory $packageFactory;

    /**
     * @var bool
     */
    protected bool $initialized = false;

    /**
     * @var array|null
     */
    protected ?array $componentsCache = null;

    /**
     * AdminPanel constructor.
     *
     * @param AdminPackageFactory $packageFactory The factory responsible for managing admin packages.
     */
    public function __construct(AdminPackageFactory $packageFactory)
    {
        $this->packageFactory = $packageFactory;
    }

    /**
     * Register an admin package.
     *
     * Delegates the registration to the AdminPackageFactory.
     *
     * @param AdminPackageInterface $package The admin package to register.
     * @return void
     */
    public function registerPackage(AdminPackageInterface $package): void
    {
        $this->packageFactory->registerPackage($package);
    }

    /**
     * Initialize the admin panel.
     *
     * Loads and initializes all admin packages from the specified directory.
     *
     * @return void
     */
    public function initialize(): void
    {
        if ($this->initialized || !user()->can('admin')) {
            return;
        }

        $this->initialized = true;

        template()->addNamespace('admin', path('app/Core/Modules/Admin/Resources/views'));

        $this->addRouterMacro();

        if (is_admin_path()) {
            $this->packageFactory->loadPackagesFromDirectory();
            $this->packageFactory->initializePackages();

            template()->getTemplateAssets()->getCompiler()->setImportPaths(path('app/Core/Modules/Admin/Resources/assets/sass'));

            $this->loadComponents();
        }
    }

    public function addRouterMacro()
    {
        if (!Router::hasMacro('screen')) {
            Router::macro('screen', function ($url, $screen) {
                $screenString = sha1($screen);

                template()->registerComponent($screenString, $screen);

                router()->any($url, function () use ($screenString, $url) {
                    $url = request()->getPathInfo();

                    if (request()->htmx()->isHtmxRequest() && !request()->htmx()->isBoosted() && request()->input('yoyo-id')) {
                        try {
                            return response()->make(template()->getYoyo()->update());
                        } catch (\Exception $e) {
                            if (is_debug()) {
                                throw $e;
                            }

                            logs()->error($e);

                            return response()->error(500, $e->getMessage());
                        }
                    }

                    return response()->view('admin::layouts.screen', [
                        'screen' => $screenString,
                        'slug' => $url
                    ]);
                });
            });
        }
    }

    public function loadComponents()
    {
        $cacheKey = 'admin_components_cache';
        $this->componentsCache = !is_debug() ? cache()->get($cacheKey) : null;

        if ($this->componentsCache !== null) {
            foreach ($this->componentsCache as $alias => $componentView) {
                template()->getBlade()->compiler()->component($componentView, $alias);
            }
            return;
        }

        $componentsDir = path('app/Core/Modules/Admin/Resources/views/components');
        $this->componentsCache = [];

        if (is_dir($componentsDir)) {
            $componentFiles = template()->getBladeFiles($componentsDir);

            foreach ($componentFiles as $componentFile) {
                $relativePath = str_replace([$componentsDir . DIRECTORY_SEPARATOR, '.blade.php'], '', $componentFile);
                $alias = str_replace(DIRECTORY_SEPARATOR, '.', $relativePath);

                $componentView = "Core.Modules.Admin.Resources.views.components." . $alias;
                $this->componentsCache[$alias] = $componentView;

                template()->getBlade()->compiler()->component($componentView, $alias);
            }

            if (!empty($this->componentsCache) && !is_debug()) {
                cache()->set($cacheKey, $this->componentsCache, 86400); // 1 day
            }
        }
    }

    /**
     * Get all menu items from all registered packages.
     *
     * Aggregates menu items to be displayed in the admin panel's navigation menu.
     *
     * @return array
     */
    public function getAllMenuItems(): array
    {
        return $this->packageFactory->getAllMenuItems();
    }
}
