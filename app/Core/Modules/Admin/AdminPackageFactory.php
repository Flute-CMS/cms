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

    public function __construct(
        EventDispatcher $dispatcher,
        string $packagesPath = 'app/Core/Modules/Admin/Packages',
        string $baseNamespace = 'Flute\Admin\Packages'
    ) {
        $this->dispatcher = $dispatcher;
        $this->packagesPath = rtrim(BASE_PATH . DIRECTORY_SEPARATOR . $packagesPath, DIRECTORY_SEPARATOR);
        $this->baseNamespace = rtrim($baseNamespace, '\\');
    }

    public function clearMenuCache(): void
    {
        $this->menuItemsCache = null;
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

        // Collect all registered items by key
        $registeredItems = [];
        $permissionsCache = [];
        $moduleItems = [];
        $itemsWithoutKey = [];

        foreach ($this->packages as $package) {
            $packageMenuItems = $package->getMenuItems();
            $moduleName = $this->getModuleNameFromPackage($package);
            $isModulePackage = $moduleName !== null;

            foreach ($packageMenuItems as $item) {
                if (!$this->checkItemPermission($item, $permissionsCache)) {
                    continue;
                }

                if (isset($item['type']) && $item['type'] === 'header') {
                    continue;
                }

                $key = $item['key'] ?? null;

                if ($key !== null) {
                    $registeredItems[$key] = $item;
                } elseif ($isModulePackage) {
                    if (!isset($moduleItems[$moduleName])) {
                        $moduleItems[$moduleName] = [];
                    }
                    $moduleItems[$moduleName][] = $item;
                } else {
                    $itemsWithoutKey[] = $item;
                }
            }
        }

        // Get menu config (with defaults)
        $menuConfig = config('admin-menu') ?? $this->getDefaultMenuConfig();
        $result = [];
        $currentSection = null;

        foreach ($menuConfig as $configItem) {
            // Section header
            if (isset($configItem['section'])) {
                if ($currentSection !== null && !empty($currentSection['items'])) {
                    $result[] = $currentSection;
                }
                $currentSection = [
                    'title' => __($configItem['section']),
                    '_section_key' => $configItem['section'],
                    'items' => [],
                ];

                continue;
            }

            $key = $configItem['key'] ?? null;
            if ($key === null) {
                continue;
            }

            // Group with children
            if (isset($configItem['children']) && is_array($configItem['children'])) {
                $children = [];
                foreach ($configItem['children'] as $childKey) {
                    if (isset($registeredItems[$childKey])) {
                        $children[] = $registeredItems[$childKey];
                        unset($registeredItems[$childKey]);
                    }
                }

                if (empty($children)) {
                    continue;
                }

                // If only 1 child - show it directly without nesting
                if (count($children) === 1) {
                    $menuItem = $children[0];
                    if (empty($menuItem['icon']) && isset($configItem['icon'])) {
                        $menuItem['icon'] = $configItem['icon'];
                    }
                    $menuItem['_config_key'] = $key;
                } else {
                    $menuItem = [
                        'title' => __($configItem['title']),
                        'icon' => $configItem['icon'] ?? 'ph.regular.folder',
                        'url' => '#',
                        'children' => $children,
                        '_config_key' => $key,
                    ];
                }

                $this->addToSection($result, $currentSection, $menuItem);
            } else {
                // Direct item
                $menuItem = null;

                if (isset($registeredItems[$key])) {
                    $menuItem = $registeredItems[$key];
                    if (isset($configItem['icon'])) {
                        $menuItem['icon'] = $configItem['icon'];
                    }
                    $menuItem['_config_key'] = $key;
                    unset($registeredItems[$key]);
                } elseif (isset($configItem['url'])) {
                    $menuItem = [
                        'title' => __($configItem['title']),
                        'icon' => $configItem['icon'] ?? 'ph.regular.circle',
                        'url' => url($configItem['url']),
                        '_config_key' => $key,
                    ];
                }

                if ($menuItem !== null) {
                    $this->addToSection($result, $currentSection, $menuItem);
                }
            }
        }

        // Add last section
        if ($currentSection !== null && !empty($currentSection['items'])) {
            $result[] = $currentSection;
        }

        // Remaining items (backward compatibility)
        $remainingItems = array_merge(array_values($registeredItems), $itemsWithoutKey);
        if (!empty($remainingItems)) {
            $result[] = [
                'title' => null,
                'items' => $remainingItems,
            ];
        }

        // Module items
        foreach ($moduleItems as $moduleName => $items) {
            if (!empty($items)) {
                $result[] = [
                    'title' => $moduleName,
                    'items' => $items,
                    'is_module' => true,
                ];
            }
        }

        // Filter empty sections
        $this->menuItemsCache = array_values(array_filter($result, static fn ($s) => !empty($s['items'])));

        return $this->menuItemsCache;
    }

    public function getDefaultMenuConfig(): array
    {
        return [
            ['key' => 'dashboard'],
            ['section' => 'admin-menu.sections.management'],
            [
                'key' => 'settings-group',
                'title' => 'admin-menu.settings',
                'icon' => 'ph.regular.gear',
                'children' => ['main-settings', 'api-keys', 'socials'],
            ],
            [
                'key' => 'users-group',
                'title' => 'admin-menu.users',
                'icon' => 'ph.regular.users',
                'children' => ['users', 'roles'],
            ],
            [
                'key' => 'content-group',
                'title' => 'admin-menu.content',
                'icon' => 'ph.regular.article',
                'children' => ['pages', 'navigation', 'footer'],
            ],
            [
                'key' => 'notifications-group',
                'title' => 'admin-menu.notifications',
                'icon' => 'ph.regular.bell',
                'children' => ['notification-templates', 'notification-broadcast'],
            ],
            ['key' => 'servers'],
            ['section' => 'admin-menu.sections.finance'],
            [
                'key' => 'finance-group',
                'title' => 'admin-menu.finance',
                'icon' => 'ph.regular.wallet',
                'children' => ['gateways', 'invoices', 'promo-codes', 'currencies'],
            ],
            ['section' => 'admin-menu.sections.extensions'],
            ['key' => 'modules'],
            ['key' => 'themes'],
            ['key' => 'marketplace'],
            ['section' => 'admin-menu.sections.system'],
            ['key' => 'updates'],
            [
                'key' => 'system-group',
                'title' => 'admin-menu.system',
                'icon' => 'ph.regular.info',
                'children' => ['logs', 'about'],
            ],
        ];
    }

    protected function addToSection(array &$result, ?array &$currentSection, array $menuItem): void
    {
        if ($currentSection !== null) {
            $currentSection['items'][] = $menuItem;
        } else {
            if (!isset($result['__main__'])) {
                $result['__main__'] = ['title' => null, '_section_key' => '', 'items' => []];
            }
            $result['__main__']['items'][] = $menuItem;
        }
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
