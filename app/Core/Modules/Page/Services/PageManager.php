<?php

namespace Flute\Core\Modules\Page\Services;

use Exception;
use Flute\Core\Database\Entities\Page;
use Flute\Core\Database\Entities\PageBlock;
use Flute\Core\Modules\Page\Controllers\PageController;
use Flute\Core\Router\Contracts\RouterInterface;
use Flute\Core\Services\UserService;
use Flute\Core\Support\FluteRequest;
use Nette\Utils\Json;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * PageManager handles page management, widget rendering, and automatic route registration.
 *
 * New features:
 * - Automatically registers routes for all existing pages in the database
 * - Provides fallback route handling for undefined pages (renders home.blade.php)
 * - Dynamically registers routes when new pages are created
 * - Handles page content rendering with widget support
 *
 * @package Flute\Core\Modules\Page\Services
 */
class PageManager
{
    protected const PAGE_CACHE_TIME = 3600;

    protected const PAGES_CACHE_KEY = 'flute.pages.all';

    protected const PAGES_CACHE_TIME = 3600;

    protected RouterInterface $router;

    protected bool $disabled = false;

    private array $permissions = [];

    private ?Page $currentPage = null;

    private FluteRequest $request;

    private UserService $userService;

    private LoggerInterface $logger;

    private WidgetManager $widgetManager;

    /**
     * Constructor method.
     */
    public function __construct(
        RouterInterface $router,
        FluteRequest $request,
        UserService $userService,
        LoggerInterface $logger,
        WidgetManager $widgetManager
    ) {
        $this->router = $router;
        $this->request = $request;
        $this->userService = $userService;
        $this->logger = $logger;
        $this->widgetManager = $widgetManager;

        if (is_installed()) {
            $this->loadAllPages();
            $this->loadCurrentPage();
        }
    }

    /**
     * Loads the current page and its permissions.
     */
    public function loadCurrentPage(): void
    {
        if (!is_cli() && !is_admin_path()) {
            $routePath = $this->request->getPathInfo();
            $cacheKey = 'flute.page.route.' . md5($routePath);

            $pageId = is_performance()
                ? cache()->callback($cacheKey, static function () use ($routePath) {
                    $page = Page::findOne(['route' => $routePath]);

                    return $page ? $page->id : null;
                }, self::PAGE_CACHE_TIME)
                : null;

            $this->currentPage = $pageId
                ? Page::findByPK($pageId)
                : Page::findOne(['route' => $routePath]);

            if ($this->currentPage) {
                $this->loadPermissions();

                template()->addGlobal('page', $this->currentPage);
            }
        }
    }

    /**
     * Renders all widgets for the current page.
     */
    public function run(): ?string
    {
        if (!$this->currentPage || $this->disabled) {
            return null;
        }

        $widgets = $this->currentPage->getBlocks();
        $content = "";

        foreach ($widgets as $widget) {
            try {
                $content .= $this->widgetManager
                    ->getWidget($widget->getWidget())
                    ->render(json_decode($widget->getSettings(), true));
            } catch (Exception $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);
                $content .= $this->hasAccessToEdit()
                    ? view('flute::partials.invalid-widget', [
                        'block' => $widget,
                        'exception' => $e->getMessage(),
                    ])->render()
                    : '';
            }
        }

        return $content;
    }

    /**
     * Retrieves the layout for a specific page path.
     *
     * @param string $path The page path.
     */
    public function getLayoutForPath(string $path): array
    {
        try {
            $page = Page::findOne(['route' => $path]);
            $layout = [];

            $hasPushContent = $this->hasPushContent();
            $hasContentWidget = false;
            $contentPosition = ['h' => 4, 'w' => 12, 'x' => 0, 'y' => 0];

            if ($page) {
                foreach ($page->getBlocks() as $block) {
                    if ($block->getWidget() === 'Content') {
                        $hasContentWidget = true;
                        $contentPosition = json_decode($block->gridstack, true) ?: $contentPosition;

                        break;
                    }
                }
            }

            if ($hasPushContent || $hasContentWidget) {
                $layout[] = [
                    'id' => 'content-widget',
                    'widgetName' => 'Content',
                    'settings' => [],
                    'gridstack' => $contentPosition,
                    'content' => $this->widgetManager->getWidget('Content')->render([]),
                    'isSystem' => true,
                ];
            }

            if ($page) {
                foreach ($page->getBlocks() as $block) {
                    try {
                        if ($block->getWidget() === 'Content') {
                            continue;
                        }

                        $settings = json_decode($block->getSettings(), true);

                        $layout[] = [
                            'id' => $block->getId(),
                            'widgetName' => $block->getWidget(),
                            'settings' => $settings,
                            'gridstack' => json_decode($block->gridstack, true),
                            'content' => $this->widgetManager->getWidget($block->getWidget())->render($settings),
                        ];
                    } catch (Exception $e) {
                        $this->logger->error("Failed to retrieve layout for path {$path}: " . $e->getMessage());
                        logs()->error($e);
                    }
                }
            }

            return $layout;
        } catch (Exception $e) {
            $this->logger->error("Failed to retrieve layout for path {$path}: " . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Renders a single widget by its ID.
     *
     * @param int $widgetId The widget ID.
     */
    public function renderWidget(int $widgetId)
    {
        try {
            $widgetDb = PageBlock::findByPK($widgetId);

            if (!$widgetDb) {
                throw new Exception('Widget ' . $widgetId . ' does not exist on current page');
            }

            $content = $this->widgetManager
                ->getWidget($widgetDb->getWidget())
                ->render(json_decode($widgetDb->getSettings(), true));

            return $content !== "" ? $content : null;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $this->hasAccessToEdit()
                ? view('flute::partials.invalid-widget', [
                    'block' => $widgetDb,
                    'exception' => $e->getMessage(),
                ])->render()
                : null;
        }
    }

    public function renderAllWidgets(): string
    {
        if (!$this->currentPage || $this->disabled) {
            return '';
        }

        $content = '';

        foreach ($this->currentPage->getBlocks() as $block) {
            $gridstack = json_decode($block->gridstack, true) ?? [];

            $style = sprintf(
                'grid-column: %d / span %d; grid-row: %d / span %d;',
                ($gridstack['x'] ?? 0) + 1,
                ($gridstack['w'] ?? 1),
                ($gridstack['y'] ?? 0) + 1,
                ($gridstack['h'] ?? 1)
            );

            $widgetContent = $this->safeRenderBlock($block);

            if ($widgetContent !== null && $widgetContent !== '') {
                $content .= '<section data-widget-id="' . $block->getId() . '" data-widget-name="' . $block->getWidget() . '" style="' . $style . '">';
                $content .= $widgetContent;
                $content .= '</section>';
            }
        }

        return $content;
    }

    /**
     * Retrieves a page by its path.
     *
     * @param string $path The page path.
     *
     * @return Page The page instance.
     */
    public function getPage(string $path): Page
    {
        $page = Page::findOne(['route' => $path]);

        if (!$page) {
            $page = new Page();
            $page->setRoute($path);
            $page->setTitle(config('app.name'));
            $page->setDescription(config('app.description'));
            $page->setKeywords(config('app.keywords'));
            $page->setRobots(config('app.robots'));
            $page->setOgImage(config('app.og_image'));
        }

        return $page;
    }

    /**
     * Saves the layout for a specific page path.
     *
     * @param string $path   The page path.
     * @param array  $layout The layout data.
     */
    public function saveLayoutForPath(string $path, array $layout): void
    {
        $page = Page::findOne(['route' => $path]);
        if (!$page) {
            $page = new Page();
            $page->setRoute($path);
            $page->setTitle(config('app.name'));
        }

        $page->removeAllBlocks();

        foreach ($layout as $item) {
            $widgetName = $item['widgetName'] ?? '';
            $settings = $item['settings'] ?? [];

            if ($widgetName === 'Content') {
                $block = new PageBlock();
                $block->setWidget($widgetName);
                $block->setSettings('{}');
                $block->setPage($page);

                $block->gridstack = isset($item['gridstack'])
                    ? Json::encode([
                        'h' => $item['gridstack']['h'] ?? 4,
                        'w' => $item['gridstack']['w'] ?? 12,
                        'x' => $item['gridstack']['x'] ?? 0,
                        'y' => $item['gridstack']['y'] ?? 0,
                        'minW' => 4,
                    ])
                    : Json::encode(['h' => 4, 'w' => 12, 'x' => 0, 'y' => 0, 'minW' => 4]);

                $page->addBlock($block);

                continue;
            }

            $widgetSettingsJson = Json::encode($settings);

            $widget = $this->widgetManager->getWidget($widgetName);

            $block = new PageBlock();
            $block->setWidget($widgetName);
            $block->setSettings($widgetSettingsJson);
            $block->setPage($page);

            $block->gridstack = isset($item['gridstack'])
                ? Json::encode([
                    'h' => $item['gridstack']['h'] ?? '',
                    'w' => $item['gridstack']['w'] ?? $widget->getDefaultWidth(),
                    'x' => $item['gridstack']['x'] ?? '',
                    'y' => $item['gridstack']['y'] ?? '',
                    'minW' => $item['gridstack']['minW'] ?? $widget->getMinWidth(),
                ])
                : '{}';

            $page->addBlock($block);
        }

        $page->saveOrFail();
    }

    /**
     * Checks if the editor is disabled.
     */
    public function isEditorDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Disables the page editor.
     */
    public function disablePageEditor(): self
    {
        $this->disabled = true;

        return $this;
    }

    /**
     * Creates a new page with specified route and parameters.
     */
    public function createPage(string $route, array $parameters = []): Page
    {
        $page = new Page();
        $page->setRoute($route);
        $page->setTitle($parameters['title'] ?? config('app.name'));
        $page->setDescription($parameters['description'] ?? config('app.description'));
        $page->setKeywords($parameters['keywords'] ?? null);
        $page->setRobots($parameters['robots'] ?? null);
        $page->setOgImage($parameters['og_image'] ?? null);

        transaction($page)->run();

        $this->registerSinglePageRoute($page);

        return $page;
    }

    /**
     * Updates parameters of the current page.
     */
    public function updatePageParameters(array $parameters): void
    {
        if (!$this->currentPage) {
            $this->currentPage = new Page();
        }

        if (isset($parameters['title'])) {
            $this->currentPage->setTitle($parameters['title']);
        }
        if (isset($parameters['description'])) {
            $this->currentPage->setDescription($parameters['description']);
        }
        if (isset($parameters['keywords'])) {
            $this->currentPage->setKeywords($parameters['keywords']);
        }
        if (isset($parameters['robots'])) {
            $this->currentPage->setRobots($parameters['robots']);
        }
        if (isset($parameters['og_image'])) {
            $this->currentPage->setOgImage($parameters['og_image']);
        }
    }

    /**
     * Saves the current page instance.
     */
    public function save()
    {
        if (!$this->currentPage) {
            throw new RuntimeException('No current page to update.');
        }

        $isNewPage = !$this->currentPage->getId();

        transaction($this->currentPage)->run();

        if ($isNewPage) {
            $this->registerSinglePageRoute($this->currentPage);
        }
    }

    /**
     * Returns the current page.
     */
    public function getCurrentPage(): ?Page
    {
        return $this->currentPage;
    }

    /**
     * Returns the blocks of the current page.
     */
    public function getBlocks(): array
    {
        return $this->currentPage ? $this->currentPage->getBlocks() : [];
    }

    /**
     * Returns the permissions array.
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * Checks if the user has access to edit pages.
     */
    public function hasAccessToEdit(): bool
    {
        return $this->userService->isLoggedIn() && $this->userService->can('admin.pages');
    }

    /**
     * Checks if the page is in edit mode.
     */
    public function isEditMode(): bool
    {
        $editMode = $this->request->query->get('editMode') ?? null;

        return $this->hasAccessToEdit() && $editMode;
    }

    /**
     * Magic getter to retrieve properties from the current page.
     */
    public function __get($name)
    {
        if ($this->currentPage && property_exists($this->currentPage, $name)) {
            return $this->currentPage->$name;
        }

        return null;
    }

    /**
     * Loads all pages and registers their routes in the router.
     */
    protected function loadAllPages(): void
    {
        if (is_admin_path()) {
            return;
        }

        $pageRoutes = is_performance()
            ? cache()->callback(self::PAGES_CACHE_KEY, static function () {
                $pages = Page::findAll();

                return array_map(static fn ($page) => [
                    'id' => $page->id,
                    'route' => $page->route,
                    'permissions' => array_map(static fn ($p) => $p->permission?->name, $page->permissions->toArray()),
                ], $pages);
            }, self::PAGES_CACHE_TIME)
            : null;

        if ($pageRoutes !== null) {
            $this->registerPageRoutesFromCache($pageRoutes);
        } else {
            $this->registerPageRoutes(Page::findAll());
        }
    }

    /**
     * Registers routes from cached page data.
     */
    protected function registerPageRoutesFromCache(array $pageRoutes): void
    {
        foreach ($pageRoutes as $pageData) {
            $this->router->get($pageData['route'], [PageController::class, 'index'])
                ->middleware('page.permissions:' . implode(',', array_filter($pageData['permissions'])));
        }
    }

    /**
     * Registers routes for all pages in the router.
     *
     * @param array $pages Array of Page entities
     */
    protected function registerPageRoutes(array $pages): void
    {
        foreach ($pages as $page) {
            if (!$page->route) {
                continue;
            }

            $this->registerSinglePageRoute($page);
        }
    }

    /**
     * Renders content for a specific page.
     *
     * @return mixed
     */
    protected function renderPageContent(Page $page)
    {
        $this->currentPage = $page;
        $this->loadPermissions();

        if (!is_cli()) {
            template()->addGlobal('page', $this->currentPage);
        }

        return response()->view('flute::pages.home');
    }

    /**
     * Renders home page for undefined routes.
     *
     * @return mixed
     */
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

    protected function safeRenderBlock(PageBlock $block): ?string
    {
        try {
            $settings = json_decode($block->getSettings(), true) ?? [];

            return $this->widgetManager->getWidget($block->getWidget())->render($settings);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $this->hasAccessToEdit()
                ? view('flute::partials.invalid-widget', [
                    'block' => $block,
                    'exception' => $e->getMessage(),
                ])->render()
                : null;
        }
    }

    /**
     * Registers a route for a single page.
     */
    protected function registerSinglePageRoute(Page $page): void
    {
        if (!$page->route) {
            return;
        }

        if ($this->router->hasRoute($page->route, 'GET')) {
            return;
        }

        foreach ($page->getPermissions() as $permission) {
            if (!user()->can($permission)) {
                return;
            }
        }

        $this->router->get($page->route, fn () => $this->renderPageContent($page));
    }

    /**
     * Loads permissions of the current page.
     */
    protected function loadPermissions(): void
    {
        foreach ($this->currentPage->getPermissions() as $permission) {
            $this->permissions[] = $permission;
        }
    }

    /**
     * Check if there's actual push content
     */
    private function hasPushContent(): bool
    {
        try {
            $pushContent = view()->yieldPushContent('content');

            return !empty(trim(strip_tags($pushContent)));
        } catch (Exception $e) {
            return true;
        }
    }
}
