<?php

namespace Flute\Core\Page;

use Flute\Core\App;
use Flute\Core\Database\Entities\Page;
use Flute\Core\Database\Entities\PageBlock;
use Flute\Core\Database\Repositories\PageRepository;
use Flute\Core\Events\RoutingFinishedEvent;
use Flute\Core\Http\Controllers\PagesController;
use Flute\Core\Http\Middlewares\CSRFMiddleware;
use Flute\Core\Router\RouteDispatcher;
use Flute\Core\Router\RouteGroup;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

/**
 * The PageManager class manages pages and their blocks, permissions, and related data.
 */
class PageManager
{
    /** @var PageRepository */
    private $pageRepository;

    /** @var array */
    private array $blocks = [];

    /** @var array */
    private array $permissions = [];

    /** @var array */
    private array $allPages = [];

    /** @var Page|null */
    private ?Page $currentPage;

    /** @var bool */
    private bool $performanceMode = false;

    protected RouteDispatcher $routeDispatcher;

    protected bool $disabled = false;

    public function __construct(RouteDispatcher $routeDispatcher)
    {
        $this->routeDispatcher = $routeDispatcher;

        // Checking if the app is in performance mode.
        $this->performanceMode = (bool) (app('app.mode') == App::PERFORMANCE_MODE);

        // Loading the current page data.
        if (is_installed()) {
            // Fetching the required repositories.
            $this->pageRepository = rep(Page::class);

            $this->loadAllPages();

            $this->loadCurrentPage();

            $this->loadRoutes();
        }
    }

    /**
     * Load the current page, blocks, and permissions.
     */
    public function loadCurrentPage(): void
    {
        // Find the page by its route.
        $this->currentPage = $this->pageRepository->findByRoute(request()->getPathInfo());

        if ($this->currentPage) {
            $this->loadBlocks();
            $this->loadPermissions();
        }
    }

    /**
     * Preventing 404 errors, if route is wasn't found, but it exists in the custom "pages"
     * 
     * It's not a super fast variant. But for alpha its good
     */
    protected function loadAllPages(): void
    {
        events()->addListener(RoutingFinishedEvent::NAME, function (RoutingFinishedEvent $event) {
            if ($event->getResponse()->getStatusCode() === 404) {
                $allPages = $this->pageRepository->select()->load('permissions')->fetchAll();

                $found = false;

                foreach ($allPages as $page) {
                    if (!request()->is($page->route))
                        continue;

                    if ($page->permissions->count() > 0) {
                        foreach ($page->permissions as $permission) {
                            if (user()->hasPermission($permission->name)) {
                                $found = $page;
                                break;
                            }
                        }
                    } else {
                        $found = $page;
                        break;
                    }
                }

                if (!$found)
                    return;

                template()->clearAllSections();

                template()->getBlade()->pushFirst('header', '<script>SITE_URL = `' . config('app.url') . '`;</script>');

                $event->setResponse(
                    view(tt('components/page'), [
                        'page' => $found
                    ])
                );

                return;
            }
        });
    }

    /**
     * Run the page editor parser on the blocks of the current page.
     * 
     * @return string|void
     */
    public function run()
    {
        if (!isset($this->currentPage) || $this->disabled) {
            return;
        }

        /** @var PageEditorParser $parser */
        $parser = app(PageEditorParser::class);

        $parse = '';

        try {
            $parse = $parser->parse($this->blocks);
        } catch (\RuntimeException $e) {
            logs()->error($e);
        }

        return $parse;
    }

    public function isEditorDisabled(): bool
    {
        return $this->disabled;
    }

    public function disablePageEditor(): self
    {
        $this->disabled = true;

        return $this;
    }

    /**
     * @throws \Throwable
     * @throws JsonException
     */
    public function savePageBlocks(array $blocks, string $path = null): void
    {
        if ($path) {
            $page = $this->pageRepository->findByRoute($path);

            if (!$page) {
                $page = $this->createPage($path);
            }

            $this->currentPage = $page;
        } elseif (!$this->currentPage) {
            $page = $this->createPage(request()->getPathInfo());
            $this->currentPage = $page;
        }

        $this->currentPage->removeAllBlocks();

        foreach ($blocks as $block) {
            $blockTest = new PageBlock;
            $blockTest->json = Json::encode($block);

            $this->currentPage->addBlock($blockTest);
        }

        transaction($this->currentPage)->run();
    }

    /**
     * @throws \Throwable
     */
    public function createPage(
        string $route,
        string $title = '',
        string $description = null,
        string $keywords = null,
        string $robots = null,
        string $og_title = null,
        string $og_description = null,
        string $og_image = null
    ): Page {
        $page = new Page;
        $page->route = $route;
        $page->title = $title ?? "Custom page";
        $page->description = $description ?? "Description of custom page";
        $page->keywords = $keywords;
        $page->robots = $robots;
        $page->og_title = $og_title ?? "Custom page";
        $page->og_description = $og_description ?? "Description of custom page";
        $page->og_image = $og_image;

        transaction($page)->run();

        return $page;
    }

    /**
     * Load the blocks of the current page.
     * @throws JsonException
     */
    protected function loadBlocks(): void
    {
        foreach ($this->currentPage->blocks as $block) {
            $this->blocks[] = Json::decode($block->json, Json::FORCE_ARRAY);
        }
    }

    /**
     * Load the permissions of the current page.
     */
    protected function loadPermissions(): void
    {
        foreach ($this->currentPage->permissions as $permission) {
            $this->permissions[] = $permission;
        }
    }

    protected function loadRoutes(): void
    {
        if ($this->hasAccessToEdit()) {
            $this->routeDispatcher->group(function (RouteGroup $routeGroup) {
                $routeGroup->middleware(CSRFMiddleware::class);
                $routeGroup->post('save', [PagesController::class, 'saveEdit']);
                $routeGroup->post('saveimage', [PagesController::class, 'saveImage']);
            }, 'page/');
        }
    }

    public function getCurrentPage(): ?Page
    {
        return $this->currentPage;
    }

    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function isPerformanceMode(): bool
    {
        return $this->performanceMode;
    }

    public function hasAccessToEdit(): bool
    {
        return user()->isLoggedIn() && user()->hasPermission('admin.pages');
    }

    public function isEditMode(): bool
    {
        return $this->hasAccessToEdit() && request()->input('editMode');
    }

    public function __get($name)
    {
        if (!isset($this->currentPage))
            return false;

        return $this->currentPage && property_exists($this->currentPage, $name) ? $this->currentPage->$name : false;
    }
}