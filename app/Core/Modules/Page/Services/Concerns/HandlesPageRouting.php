<?php

namespace Flute\Core\Modules\Page\Services\Concerns;

use Flute\Core\Database\Entities\Page;
use Flute\Core\Modules\Page\Controllers\PageController;
use Throwable;

trait HandlesPageRouting
{
    protected function loadAllPages(): void
    {
        if (self::$pagesLoaded) {
            return;
        }

        if (is_admin_path()) {
            return;
        }

        try {
            $pageRoutes = cache()->callback(
                self::PAGES_CACHE_KEY,
                static function () {
                    $pages = Page::query()->load('permissions')->fetchAll();

                    return self::mapPagesToRoutes($pages);
                },
                is_performance() ? self::PAGES_CACHE_TIME : 30,
            );
        } catch (Throwable $e) {
            $pageRoutes = $this->useCachedPageRoutes($e, 'page route lookup');
        }

        $this->registerPageRoutesFromCache($pageRoutes);

        self::$pagesLoaded = true;
    }

    protected function registerPageRoutesFromCache(array $pageRoutes): void
    {
        foreach ($pageRoutes as $pageData) {
            $route = $pageData['route'] ?? null;
            $permissions = array_filter($pageData['permissions'] ?? []);

            if (!$route) {
                continue;
            }

            if ($this->router->hasRoute($route, 'GET')) {
                continue;
            }

            $this->router->get($route, [PageController::class, 'index'])->middleware(
                'page.permissions:' . implode(',', $permissions),
            );
        }
    }

    protected function registerPageRoutes(array $pages): void
    {
        foreach ($pages as $page) {
            if (!$page->route) {
                continue;
            }

            $this->registerSinglePageRoute($page);
        }
    }

    protected function renderHomePage(string $routePath)
    {
        $tempPage = new Page();
        $tempPage->setRoute($routePath);
        $tempPage->setTitle(config('app.name'));
        $tempPage->setDescription(config('app.description'));
        $tempPage->setKeywords(config('app.keywords'));
        $tempPage->setRobots(config('app.robots'));
        $tempPage->setOgImage(config('app.og_image'));

        $this->currentPage = $tempPage;

        if (!is_cli()) {
            template()->addGlobal('page', $this->currentPage);
        }

        return view('flute::pages.home');
    }

    protected function registerSinglePageRoute(Page $page): void
    {
        if (!$page->route) {
            return;
        }

        if ($this->router->hasRoute($page->route, 'GET')) {
            return;
        }

        $permissions = array_filter($page->getPermissions() ?? []);

        $this->router->get($page->route, [PageController::class, 'index'])->middleware(
            'page.permissions:' . implode(',', $permissions),
        );
    }

    protected static function mapPagesToRoutes(array $pages): array
    {
        return array_map(static function ($page) {
            $perms = $page->permissions ?? [];

            if (is_object($perms) && method_exists($perms, 'toArray')) {
                $perms = $perms->toArray();
            } elseif (!is_array($perms)) {
                $perms = [];
            }

            $permissions = array_map(static function ($p) {
                if (is_object($p)) {
                    return $p->permission?->name ?? $p->name ?? null;
                }

                if (is_array($p)) {
                    return $p['permission']['name'] ?? $p['name'] ?? null;
                }

                return null;
            }, $perms);

            return [
                'id' => $page->id,
                'route' => $page->route,
                'permissions' => array_filter($permissions),
            ];
        }, $pages);
    }

    protected function useCachedPageRoutes(Throwable $e, string $context): array
    {
        $this->handlePagesDatabaseFailure($e, $context);

        try {
            $cachedRoutes = cache()->get(self::PAGES_CACHE_KEY, []);
        } catch (Throwable) {
            $cachedRoutes = [];
        }

        return is_array($cachedRoutes) ? $cachedRoutes : [];
    }

    protected function handlePagesDatabaseFailure(Throwable $e, string $context): void
    {
        $this->pagesDatabaseUnavailable = true;

        if ($this->pagesDatabaseFailureLogged) {
            return;
        }

        $this->logger->warning("Page database {$context} failed, using degraded page state: " . $e->getMessage());
        $this->pagesDatabaseFailureLogged = true;
    }
}
