<?php

namespace Flute\Admin;

use Flute\Admin\Contracts\AdminPackageInterface;
use InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AdminPackageFactory
{
    /**
     * @var AdminPackageInterface[]
     */
    protected array $packages = [];

    protected EventDispatcher $dispatcher;

    protected string $packagesPath;

    protected string $baseNamespace;

    protected ?array $menuItemsCache = null;

    protected bool $packagesLoaded = false;

    public function clearMenuCache(): void
    {
        $this->menuItemsCache = null;
    }

    public function __construct(
        EventDispatcher $dispatcher,
        string $packagesPath = 'app/Core/Modules/Admin/Packages',
        string $baseNamespace = 'Flute\Admin\Packages'
    ) {
        $this->dispatcher = $dispatcher;
        $this->packagesPath = rtrim(BASE_PATH . DIRECTORY_SEPARATOR . $packagesPath, DIRECTORY_SEPARATOR);
        $this->baseNamespace = rtrim($baseNamespace, '\\');
    }

    public function registerPackage(AdminPackageInterface $package): void
    {
        $this->packages[] = $package;
        $this->menuItemsCache = null;

        $event = new Events\PackageRegisteredEvent($package);
        $this->dispatcher->dispatch($event, Events\PackageRegisteredEvent::NAME);
    }

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
     * @return AdminPackageInterface[]
     */
    public function getPackages(): array
    {
        return $this->packages;
    }

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

            /** @var AdminPackageInterface $package */
            $package = app()->get($className);

            if (($package->getPermissions() && user()->can($package->getPermissions())) || is_cli()) {
                $this->registerPackage($package);
            }
        }

        if (!empty($packageClasses) && !is_debug()) {
            cache()->set($cacheKey, $packageClasses, 86400);
        }

        $this->packagesLoaded = true;
    }

    public function getAllMenuItems(): array
    {
        if ($this->menuItemsCache !== null) {
            return $this->menuItemsCache;
        }

        $sections = [];
        $permissionsCache = [];
        // Remember last explicit header from CORE admin packages so follow-up core items
        // without a header (e.g. MainSettings) can be grouped nicely.
        $lastCoreSectionKey = null;

        foreach ($this->packages as $package) {
            $packageMenuItems = $package->getMenuItems();
            $moduleName = $this->getModuleNameFromPackage($package);
            $isModulePackage = $moduleName !== null;
            $packageSectionKey = null; // last explicit header inside this package
            $moduleSectionKey = $isModulePackage ? ('module_' . $moduleName) : null;

            foreach ($packageMenuItems as $item) {
                if (!$this->checkItemPermission($item, $permissionsCache)) {
                    continue;
                }

                if (isset($item['type']) && $item['type'] === 'header') {
                    $sectionKey = $item['title'];
                    $packageSectionKey = $sectionKey;
                    
                    if (!isset($sections[$sectionKey])) {
                        $sections[$sectionKey] = [
                            'type' => 'header',
                            'title' => $item['title'],
                            'items' => [],
                            'priority' => $package->getPriority(),
                        ];
                    } else {
                        // Keep earliest priority for stable ordering.
                        $sections[$sectionKey]['priority'] = min($sections[$sectionKey]['priority'], $package->getPriority());
                    }
                    
                    if (!$isModulePackage) {
                        $lastCoreSectionKey = $sectionKey;
                    }
                } else {
                    // Section resolution rules:
                    // 1) explicit header within this package
                    // 2) module auto-section (ONLY for module packages)
                    // 3) last core header (ONLY for core packages)
                    // 4) ungrouped fallback
                    $targetSection = $packageSectionKey;
                    if ($targetSection === null && $isModulePackage) {
                        $targetSection = $moduleSectionKey;
                    }
                    if ($targetSection === null && !$isModulePackage) {
                        $targetSection = $lastCoreSectionKey;
                    }
                    
                    if ($targetSection === null) {
                        $targetSection = '__ungrouped__';
                        if (!isset($sections[$targetSection])) {
                            $sections[$targetSection] = [
                                'type' => 'header',
                                'title' => null,
                                'items' => [],
                                'priority' => 0,
                            ];
                        }
                    }

                    // Ensure module section exists (when used).
                    if ($isModulePackage && $targetSection === $moduleSectionKey && !isset($sections[$targetSection])) {
                        $sections[$targetSection] = [
                            'type' => 'header',
                            'title' => $moduleName,
                            'items' => [],
                            'priority' => $package->getPriority(),
                            'is_module' => true,
                        ];
                    }

                    // Ensure core header exists when used as fallback (rare but possible).
                    if (!$isModulePackage && $targetSection === $lastCoreSectionKey && $targetSection !== null && !isset($sections[$targetSection])) {
                        $sections[$targetSection] = [
                            'type' => 'header',
                            'title' => $targetSection,
                            'items' => [],
                            'priority' => $package->getPriority(),
                        ];
                    }

                    $sections[$targetSection]['items'][] = $item;
                }
            }
        }

        uasort($sections, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        $result = [];
        foreach ($sections as $section) {
            if (!empty($section['items'])) {
                $result[] = $section;
            }
        }

        $this->menuItemsCache = $result;

        return $result;
    }

    protected function getModuleNameFromPackage(AdminPackageInterface $package): ?string
    {
        $basePath = $package->getBasePath();
        
        if (strpos($basePath, 'app' . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR) !== false) {
            preg_match('/app[\/\\\\]Modules[\/\\\\]([^\/\\\\]+)/', $basePath, $matches);
            if (!empty($matches[1])) {
                return $matches[1];
            }
        }

        return null;
    }

    protected function checkItemPermission(array $item, array &$permissionsCache): bool
    {
        if (!isset($item['permission'])) {
            return true;
        }

        $permissions = $item['permission'];
        $mode = isset($item['permission_mode']) ? strtolower($item['permission_mode']) : 'all';
        $cacheKey = $this->getPermissionCacheKey($permissions, $mode);

        if (!isset($permissionsCache[$cacheKey])) {
            $permissionsCache[$cacheKey] = $this->userHasPermissions($permissions, $mode);
        }

        return $permissionsCache[$cacheKey];
    }

    protected function getPermissionCacheKey($permissions, string $mode): string
    {
        if (is_array($permissions)) {
            return $mode . '_' . implode('_', $permissions);
        }

        return 'single_' . $permissions;
    }

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
            }
            
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
