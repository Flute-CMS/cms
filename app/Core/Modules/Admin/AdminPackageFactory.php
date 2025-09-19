<?php

namespace Flute\Admin;

use Flute\Admin\Contracts\AdminPackageInterface;
use InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class AdminPackageFactory
 *
 * Responsible for registering and initializing admin packages.
 */
class AdminPackageFactory
{
    /**
     * @var AdminPackageInterface[]
     */
    protected array $packages = [];

    /**
     */
    protected EventDispatcher $dispatcher;

    /**
     */
    protected string $packagesPath;

    /**
     */
    protected string $baseNamespace;

    /**
     */
    protected ?array $menuItemsCache = null;

    /**
     */
    protected bool $packagesLoaded = false;

    /**
     * AdminPackageFactory constructor.
     *
     * @param EventDispatcher $dispatcher The event dispatcher instance.
     * @param string $packagesPath The path to the packages directory.
     * @param string $baseNamespace The base namespace for the packages.
     */
    public function __construct(
        EventDispatcher $dispatcher,
        string $packagesPath = 'app/Core/Modules/Admin/Packages',
        string $baseNamespace = 'Flute\Admin\Packages'
    ) {
        $this->dispatcher = $dispatcher;
        $this->packagesPath = rtrim(BASE_PATH . DIRECTORY_SEPARATOR . $packagesPath, DIRECTORY_SEPARATOR);
        $this->baseNamespace = rtrim($baseNamespace, '\\');
    }

    /**
     * Registers a package.
     *
     * Adds the package to the internal list and registers its namespace if provided.
     *
     * @param AdminPackageInterface $package The admin package to register.
     */
    public function registerPackage(AdminPackageInterface $package): void
    {
        $this->packages[] = $package;
        $this->menuItemsCache = null;

        $event = new Events\PackageRegisteredEvent($package);
        $this->dispatcher->dispatch($event, Events\PackageRegisteredEvent::NAME);
    }

    /**
     * Initializes all registered packages.
     *
     * Calls the initialize and boot methods on all registered packages.
     */
    public function initializePackages(): void
    {
        if (empty($this->packages)) {
            return;
        }

        usort($this->packages, static fn (AdminPackageInterface $a, AdminPackageInterface $b) => $a->getPriority() <=> $b->getPriority());

        foreach ($this->packages as $package) {
            $package->initialize();

            $event = new Events\PackageInitializedEvent($package);
            $this->dispatcher->dispatch($event, Events\PackageInitializedEvent::NAME);
        }

        foreach ($this->packages as $package) {
            $package->boot();
        }
    }

    /**
     * Retrieves all registered packages.
     *
     * @return AdminPackageInterface[]
     */
    public function getPackages(): array
    {
        return $this->packages;
    }

    /**
     * Automatically loads and registers packages from the specified directory.
     *
     * Scans the packages directory for PHP files, instantiates packages that implement AdminPackageInterface,
     * and registers them if the current user has the necessary permissions.
     *
     * @throws InvalidArgumentException If the packages directory does not exist.
     */
    public function loadPackagesFromDirectory(): void
    {
        if ($this->packagesLoaded) {
            return;
        }

        if (!is_dir($this->packagesPath)) {
            throw new InvalidArgumentException("Packages directory does not exist: {$this->packagesPath}");
        }

        $cacheKey = 'admin_packages_' . md5($this->packagesPath);
        $cachedPackages = cache()->get($cacheKey);

        if ($cachedPackages !== null && !is_debug()) {
            foreach ($cachedPackages as $className) {
                if (!class_exists($className)) {
                    continue;
                }

                $package = app()->get($className);

                if (($package->getPermissions() && user()->can($package->getPermissions())) || is_cli()) {
                    $this->registerPackage($package);
                }
            }

            $this->packagesLoaded = true;

            return;
        }

        $finder = finder();
        $finder->files()->in($this->packagesPath)->depth('< 2')->name('*.php');

        $packageClasses = [];

        foreach ($finder as $file) {
            $relativePath = $file->getRelativePath();

            $className = $this->baseNamespace;
            if ($relativePath) {
                $className .= '\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
            }

            $className .= '\\' . $file->getBasename('.php');

            if (!class_exists($className)) {
                continue;
            }

            if (!is_subclass_of($className, AdminPackageInterface::class)) {
                continue;
            }

            $packageClasses[] = $className;

            /**
             * @var AdminPackageInterface $package
             */
            $package = app()->get($className);

            // We support 2 mods. Default web and CLI for getting routes and other things
            if (($package->getPermissions() && user()->can($package->getPermissions())) || is_cli()) {
                $this->registerPackage($package);
            }
        }

        if (!empty($packageClasses) && !is_debug()) {
            cache()->set($cacheKey, $packageClasses, 86400); // 1 day
        }

        $this->packagesLoaded = true;
    }

    /**
     * Get all menu items from registered packages.
     *
     * Aggregates menu items from all registered packages for display in the admin navigation.
     */
    public function getAllMenuItems(): array
    {
        if ($this->menuItemsCache !== null) {
            return $this->menuItemsCache;
        }

        $menuItems = [];
        $permissionsCache = [];

        foreach ($this->packages as $package) {
            $packageMenuItems = $package->getMenuItems();
            foreach ($packageMenuItems as $item) {
                if (isset($item['permission'])) {
                    $permissions = $item['permission'];
                    $mode = isset($item['permission_mode']) ? strtolower($item['permission_mode']) : 'all';

                    $cacheKey = $this->getPermissionCacheKey($permissions, $mode);

                    if (!isset($permissionsCache[$cacheKey])) {
                        $permissionsCache[$cacheKey] = $this->userHasPermissions($permissions, $mode);
                    }

                    if ($permissionsCache[$cacheKey]) {
                        $menuItems[] = $item;
                    }
                } else {
                    $menuItems[] = $item;
                }
            }
        }

        $this->menuItemsCache = $menuItems;

        return $menuItems;
    }

    /**
     * Creates a cache key for permission checks
     *
     * @param string|array $permissions
     */
    protected function getPermissionCacheKey($permissions, string $mode): string
    {
        if (is_array($permissions)) {
            return $mode . '_' . implode('_', $permissions);
        }

        return 'single_' . $permissions;
    }

    /**
     * Checks if the user has the necessary permissions for a menu item.
     *
     * @param string|array $permissions Permission or an array of permissions.
     * @param string $mode Check mode: 'all' or 'any'.
     */
    protected function userHasPermissions($permissions, string $mode = 'all'): bool
    {
        if (is_array($permissions)) {
            if ($mode === 'any') {
                foreach ($permissions as $perm) {
                    if (user()->can($perm)) {
                        return true;
                    }
                }

                return false;
            }   // 'all'
            foreach ($permissions as $perm) {
                if (!user()->can($perm)) {
                    return false;
                }
            }

            return true;

        }

        return user()->can($permissions);

    }
}
