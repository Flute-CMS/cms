<?php

namespace Flute\Core\Modules\Page\Services;

use Exception;
use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Database\Entities\GlobalPageBlock;
use Flute\Core\Database\Entities\Page;
use Flute\Core\Database\Entities\PageBlock;
use Flute\Core\Modules\Page\Controllers\PageController;
use Flute\Core\Router\Contracts\RouterInterface;
use Flute\Core\Services\UserService;
use Flute\Core\Support\FluteRequest;
use Nette\Utils\Json;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

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

    protected const GLOBAL_LAYOUT_CACHE_KEY = 'flute.global.layout';

    protected const GLOBAL_LAYOUT_CACHE_TIME = 3600;

    protected RouterInterface $router;

    protected bool $disabled = false;

    private bool $globalContentRendered = false;

    private static bool $pagesLoaded = false;

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
        WidgetManager $widgetManager,
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
                ? cache()->callback(
                    $cacheKey,
                    static function () use ($routePath) {
                        $page = Page::findOne(['route' => $routePath]);

                        return $page ? $page->id : null;
                    },
                    self::PAGE_CACHE_TIME,
                )
                : null;

            $this->currentPage = $pageId ? Page::findByPK($pageId) : Page::findOne(['route' => $routePath]);

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
        $content = '';

        foreach ($widgets as $widget) {
            try {
                $widgetName = $widget->getWidget();
                $startTime = microtime(true);
                $content .= $this->widgetManager
                    ->getWidget($widgetName)
                    ->render(json_decode($widget->getSettings(), true));
                WidgetRenderTiming::add($widgetName, microtime(true) - $startTime);
            } catch (Throwable $e) {
                $this->logger->error('Widget render error: ' . $e->getMessage(), [
                    'widget' => $widget->getWidget(),
                    'block_id' => $widget->getId(),
                    'exception' => $e,
                ]);
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
                        $widgetName = $block->getWidget();

                        $startTime = microtime(true);
                        $widgetContent = $this->widgetManager->getWidget($widgetName)->render($settings);
                        WidgetRenderTiming::add($widgetName, microtime(true) - $startTime);

                        $conditionsRaw = $block->getConditions();

                        $layout[] = [
                            'id' => $block->getId(),
                            'widgetName' => $widgetName,
                            'settings' => $settings,
                            'gridstack' => json_decode($block->gridstack, true),
                            'content' => $widgetContent,
                            'conditions' => $conditionsRaw
                                ? json_decode($conditionsRaw, true) ?? ['auth' => 'all', 'device' => 'all']
                                : ['auth' => 'all', 'device' => 'all'],
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
        $widgetDb = null;

        try {
            $widgetDb = PageBlock::findByPK($widgetId);

            if (!$widgetDb) {
                throw new Exception('Widget ' . $widgetId . ' does not exist on current page');
            }

            $widgetName = $widgetDb->getWidget();
            $startTime = microtime(true);
            $content = $this->widgetManager
                ->getWidget($widgetName)
                ->render(json_decode($widgetDb->getSettings(), true));
            WidgetRenderTiming::add($widgetName, microtime(true) - $startTime);

            return $content !== '' ? $content : null;
        } catch (Throwable $e) {
            $this->logger->error('Widget render error: ' . $e->getMessage(), [
                'widget_id' => $widgetId,
                'widget' => $widgetDb?->getWidget(),
                'exception' => $e,
            ]);

            return $this->hasAccessToEdit() && $widgetDb
                ? view('flute::partials.invalid-widget', [
                    'block' => $widgetDb,
                    'exception' => $e->getMessage(),
                ])->render()
                : null;
        }
    }

    public function renderAllWidgets(): string
    {
        if ($this->disabled) {
            return '';
        }

        $globalBlocks = $this->getGlobalBlocks();

        // If there are global blocks, render global layout with local content inside Content widget
        if (!empty($globalBlocks)) {
            return $this->renderGlobalLayout($globalBlocks);
        }

        // Fallback to local-only rendering if no global layout exists
        if (!$this->currentPage) {
            return '';
        }

        return $this->renderBlocksAsGrid($this->currentPage->getBlocks(), false);
    }

    /**
     * Gets all global page blocks sorted by order.
     *
     * @return GlobalPageBlock[]
     */
    public function getGlobalBlocks(): array
    {
        try {
            return GlobalPageBlock::query()->orderBy('sortOrder', 'ASC')->fetchAll();
        } catch (Throwable $e) {
            $this->logger->error('Failed to load global blocks: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Gets the global layout for the editor.
     */
    public function getGlobalLayout(): array
    {
        $layout = [];
        $globalBlocks = $this->getGlobalBlocks();

        foreach ($globalBlocks as $block) {
            try {
                $settings = json_decode($block->getSettings(), true) ?? [];
                $widgetName = $block->getWidget();

                $startTime = microtime(true);
                $widgetContent = $this->widgetManager->getWidget($widgetName)->render($settings);
                WidgetRenderTiming::add($widgetName, microtime(true) - $startTime);

                $excludedPathsRaw = $block->getExcludedPaths();
                $excludedPaths = $excludedPathsRaw ? json_decode($excludedPathsRaw, true) ?? [] : [];

                $conditionsRaw = $block->getConditions();

                $layout[] = [
                    'id' => $block->getId(),
                    'widgetName' => $widgetName,
                    'settings' => $settings,
                    'gridstack' => json_decode($block->gridstack, true),
                    'content' => $widgetContent,
                    'isSystem' => $widgetName === 'Content',
                    'excludedPaths' => $excludedPaths,
                    'conditions' => $conditionsRaw
                        ? json_decode($conditionsRaw, true) ?? ['auth' => 'all', 'device' => 'all']
                        : ['auth' => 'all', 'device' => 'all'],
                ];
            } catch (Exception $e) {
                $this->logger->error('Failed to retrieve global layout widget: ' . $e->getMessage());
            }
        }

        return $layout;
    }

    /**
     * Saves the global layout.
     *
     * @throws RuntimeException If Content widget is missing
     */
    public function saveGlobalLayout(array $layout): void
    {
        // Validate that Content widget exists in global layout
        $hasContent = false;
        foreach ($layout as $item) {
            if (( $item['widgetName'] ?? '' ) === 'Content') {
                $hasContent = true;

                break;
            }
        }

        if (!$hasContent) {
            throw new RuntimeException(__('page.global_layout_requires_content'));
        }

        // Sort layout by gridstack y-position so sortOrder matches visual order
        usort($layout, static function ($a, $b) {
            $ay = $a['gridstack']['y'] ?? 0;
            $by = $b['gridstack']['y'] ?? 0;
            $cmp = $ay <=> $by;

            return $cmp !== 0 ? $cmp : ( $a['gridstack']['x'] ?? 0 ) <=> ( $b['gridstack']['x'] ?? 0 );
        });

        // Delete all existing global blocks
        $existingBlocks = GlobalPageBlock::findAll();
        foreach ($existingBlocks as $block) {
            $block->delete();
        }

        $sortOrder = 0;

        foreach ($layout as $item) {
            $widgetName = $item['widgetName'] ?? '';
            $settings = $item['settings'] ?? [];

            $block = new GlobalPageBlock();
            $block->setWidget($widgetName);
            $block->setSettings(Json::encode($settings));
            $block->setSortOrder($sortOrder++);

            $block->gridstack = isset($item['gridstack'])
                ? Json::encode([
                    'h' => $item['gridstack']['h'] ?? 4,
                    'w' => $item['gridstack']['w'] ?? 12,
                    'x' => $item['gridstack']['x'] ?? 0,
                    'y' => $item['gridstack']['y'] ?? 0,
                    'minW' => $item['gridstack']['minW'] ?? 4,
                ])
                : Json::encode(['h' => 4, 'w' => 12, 'x' => 0, 'y' => 0, 'minW' => 4]);

            $excludedPaths = $item['excludedPaths'] ?? [];
            if (!empty($excludedPaths) && is_array($excludedPaths)) {
                $block->setExcludedPaths(Json::encode(array_values(array_filter(array_map('trim', $excludedPaths)))));
            } else {
                $block->setExcludedPaths(null);
            }

            $conditions = $item['conditions'] ?? null;
            if (
                $conditions
                && is_array($conditions)
                && ( ( $conditions['auth'] ?? 'all' ) !== 'all' || ( $conditions['device'] ?? 'all' ) !== 'all' )
            ) {
                $block->setConditions(Json::encode($conditions));
            } else {
                $block->setConditions(null);
            }

            $block->saveOrFail();
        }

        // Clear global layout cache
        if (is_performance()) {
            cache()->delete(self::GLOBAL_LAYOUT_CACHE_KEY);
        }
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

        // Sort layout by gridstack y-position so block order matches visual order
        usort($layout, static function ($a, $b) {
            $ay = $a['gridstack']['y'] ?? 0;
            $by = $b['gridstack']['y'] ?? 0;
            $cmp = $ay <=> $by;

            return $cmp !== 0 ? $cmp : ( $a['gridstack']['x'] ?? 0 ) <=> ( $b['gridstack']['x'] ?? 0 );
        });

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

            $conditions = $item['conditions'] ?? null;
            if (
                $conditions
                && is_array($conditions)
                && ( ( $conditions['auth'] ?? 'all' ) !== 'all' || ( $conditions['device'] ?? 'all' ) !== 'all' )
            ) {
                $block->setConditions(Json::encode($conditions));
            } else {
                $block->setConditions(null);
            }

            $page->addBlock($block);
        }

        $page->saveOrFail();
    }

    /**
     * Checks whether the global Content widget was rendered during renderAllWidgets().
     * Used by the layout template to avoid rendering @stack('content') twice.
     */
    public function isGlobalContentRendered(): bool
    {
        return $this->globalContentRendered;
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
     * Checks if there are any blocks to render (local or global).
     */
    public function hasAnyBlocks(): bool
    {
        if ($this->currentPage && !empty($this->currentPage->getBlocks())) {
            return true;
        }

        return !empty($this->getGlobalBlocks());
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
     * Renders content for a specific page.
     *
     * @return mixed
     */
    public function renderPageContent(Page $page)
    {
        $this->currentPage = $page;
        $this->loadPermissions();

        if (!is_cli()) {
            template()->addGlobal('page', $this->currentPage);
        }

        return response()->view('flute::pages.home');
    }

    /**
     * Renders the global layout with local widgets inside Content placeholder.
     */
    protected function renderGlobalLayout(array $globalBlocks): string
    {
        $content = '';
        $localBlocks = $this->currentPage ? $this->currentPage->getBlocks() : [];
        $localHasContentWidget = false;

        foreach ($localBlocks as $block) {
            if ($block->getWidget() === 'Content') {
                $localHasContentWidget = true;

                break;
            }
        }

        $localContent = !empty($localBlocks) ? $this->renderBlocksAsGrid($localBlocks, false) : '';

        $currentPath = $this->request->getPathInfo();

        usort($globalBlocks, static function ($a, $b) {
            $aGs = json_decode($a->gridstack, true) ?? [];
            $bGs = json_decode($b->gridstack, true) ?? [];
            $cmp = ( $aGs['y'] ?? 0 ) <=> ( $bGs['y'] ?? 0 );

            return $cmp !== 0 ? $cmp : ( $aGs['x'] ?? 0 ) <=> ( $bGs['x'] ?? 0 );
        });

        // Filter out blocks hidden by conditions
        $visibleBlocks = array_filter($globalBlocks, function ($block) use ($currentPath) {
            if ($this->isBlockExcludedForPath($block, $currentPath)) {
                return false;
            }

            return !( $block->getWidget() !== 'Content' && $this->isBlockHiddenByConditions($block) );
        });

        // Recompact to eliminate gaps from filtered blocks
        $visibleBlocks = $this->recompactBlocks(array_values($visibleBlocks));

        foreach ($visibleBlocks as $block) {
            $style = $this->getBlockGridStyle($block);
            $widgetName = $block->getWidget();

            if ($widgetName === 'Content') {
                $this->globalContentRendered = true;

                // If local blocks don't have their own Content widget,
                // insert a marker so the Blade template can inject @stack('content') here
                $localContentToRender = $localContent;
                if (!$localHasContentWidget) {
                    $localContentToRender = '<!-- __FLUTE_GLOBAL_CONTENT__ -->' . $localContent;
                }

                $content .= view('flute::partials.widget-content-section', [
                    'widgetId' => 'global-' . $block->getId(),
                    'style' => $style,
                    'localContent' => $localContentToRender,
                    'wrapGrid' => !empty($localBlocks),
                ])->render();

                continue;
            }

            $widgetContent = $this->safeRenderGlobalBlock($block);

            if ($widgetContent !== null && $widgetContent !== '') {
                $content .= view('flute::partials.widget-section', [
                    'widgetId' => 'global-' . $block->getId(),
                    'widgetName' => $widgetName,
                    'style' => $style,
                    'content' => $widgetContent,
                ])->render();
            }
        }

        return $content;
    }

    /**
     * Renders blocks as a CSS grid layout.
     *
     * @param array $blocks Array of PageBlock entities
     * @param bool $isGlobal Whether these are global blocks
     */
    protected function renderBlocksAsGrid(array $blocks, bool $isGlobal = false): string
    {
        $content = '';

        // Filter out blocks hidden by conditions
        $blocks = array_filter(
            $blocks,
            fn($block) => $block->getWidget() === 'Content' || !$this->isBlockHiddenByConditions($block),
        );

        // Recompact to eliminate gaps
        $blocks = $this->recompactBlocks(array_values($blocks));

        foreach ($blocks as $block) {
            $style = $this->getBlockGridStyle($block);

            $widgetContent = $isGlobal ? $this->safeRenderGlobalBlock($block) : $this->safeRenderBlock($block);

            if ($widgetContent !== null && $widgetContent !== '') {
                $prefix = $isGlobal ? 'global-' : '';
                $content .= view('flute::partials.widget-section', [
                    'widgetId' => $prefix . $block->getId(),
                    'widgetName' => $block->getWidget(),
                    'style' => $style,
                    'content' => $widgetContent,
                ])->render();
            }
        }

        return $content;
    }

    /**
     * Get CSS grid style for a block.
     *
     * @param PageBlock|GlobalPageBlock $block
     */
    protected function getBlockGridStyle($block): string
    {
        $gridstack = json_decode($block->gridstack, true) ?? [];

        return sprintf('grid-column: %d / span %d;', ( $gridstack['x'] ?? 0 ) + 1, $gridstack['w'] ?? 1);
    }

    /**
     * Safely renders a global block.
     */
    protected function safeRenderGlobalBlock(GlobalPageBlock $block): ?string
    {
        try {
            $settings = json_decode($block->getSettings(), true) ?? [];
            $widgetName = $block->getWidget();

            $startTime = microtime(true);
            $content = $this->widgetManager->getWidget($widgetName)->render($settings);
            WidgetRenderTiming::add($widgetName, microtime(true) - $startTime);

            return $content;
        } catch (Throwable $e) {
            $this->logger->error('Global widget render error: ' . $e->getMessage(), [
                'widget' => $block->getWidget(),
                'block_id' => $block->getId(),
                'exception' => $e,
            ]);

            return $this->hasAccessToEdit()
                ? view('flute::partials.invalid-widget', [
                    'block' => $block,
                    'exception' => $e->getMessage(),
                ])->render()
                : null;
        }
    }

    /**
     * Loads all pages and registers their routes in the router.
     */
    protected function loadAllPages(): void
    {
        if (self::$pagesLoaded) {
            return;
        }

        if (is_admin_path()) {
            return;
        }

        $pageRoutes = is_performance()
            ? cache()->callback(
                self::PAGES_CACHE_KEY,
                static function () {
                    $pages = Page::findAll();

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
                },
                self::PAGES_CACHE_TIME,
            )
            : null;

        if ($pageRoutes !== null) {
            $this->registerPageRoutesFromCache($pageRoutes);
        } else {
            $this->registerPageRoutes(Page::findAll());
        }

        self::$pagesLoaded = true;
    }

    /**
     * Registers routes from cached page data.
     */
    protected function registerPageRoutesFromCache(array $pageRoutes): void
    {
        foreach ($pageRoutes as $pageData) {
            $route = $pageData['route'];
            $permissions = array_filter($pageData['permissions']);

            if ($this->router->hasRoute($route, 'GET')) {
                continue;
            }

            $this->router->get($route, [PageController::class, 'index'])->middleware(
                'page.permissions:' . implode(',', $permissions),
            );
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
            $widgetName = $block->getWidget();

            $startTime = microtime(true);
            $content = $this->widgetManager->getWidget($widgetName)->render($settings);
            WidgetRenderTiming::add($widgetName, microtime(true) - $startTime);

            return $content;
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'Undefined schema')) {
                try {
                    $extraModules = [];
                    if (preg_match('/Flute\\\\Modules\\\\([^\\\\`]+)\\\\/i', $e->getMessage(), $m)) {
                        $extraModules[] = $m[1];
                    }
                    app(DatabaseConnection::class)->forceRefreshSchemaDeferred($extraModules);
                } catch (Throwable) {
                }
            }

            $this->logger->error('Widget render error: ' . $e->getMessage(), [
                'widget' => $block->getWidget(),
                'block_id' => $block->getId(),
                'exception' => $e,
            ]);

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

        $permissions = array_filter($page->getPermissions() ?? []);

        $this->router->get($page->route, [PageController::class, 'index'])->middleware(
            'page.permissions:' . implode(',', $permissions),
        );
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

    /**
     * Checks if a block should be hidden based on its visibility conditions.
     *
     * @param PageBlock|GlobalPageBlock $block
     */
    private function isBlockHiddenByConditions($block): bool
    {
        $raw = $block->getConditions();
        if (!$raw) {
            return false;
        }

        $conditions = json_decode($raw, true);
        if (!is_array($conditions)) {
            return false;
        }

        // Auth condition
        $authCondition = $conditions['auth'] ?? 'all';
        if ($authCondition !== 'all') {
            $isLoggedIn = $this->userService->isLoggedIn();
            if ($authCondition === 'auth' && !$isLoggedIn) {
                return true;
            }
            if ($authCondition === 'guest' && $isLoggedIn) {
                return true;
            }
        }

        // Roles condition
        $rolesCondition = $conditions['roles'] ?? [];
        if (!empty($rolesCondition) && is_array($rolesCondition)) {
            if (!$this->userService->isLoggedIn()) {
                return true; // Not logged in — can't match any role
            }

            $user = $this->userService->getCurrentUser();
            $hasMatchingRole = false;

            foreach ($user->getRoles() as $userRole) {
                $roleId = is_object($userRole) ? $userRole->role->id ?? $userRole->id ?? null : $userRole;
                if ($roleId !== null && in_array((int) $roleId, $rolesCondition, true)) {
                    $hasMatchingRole = true;

                    break;
                }
            }

            if (!$hasMatchingRole) {
                return true;
            }
        }

        // Device condition — checked via user agent
        $deviceCondition = $conditions['device'] ?? 'all';
        if ($deviceCondition !== 'all') {
            $userAgent = $this->request->headers->get('User-Agent', '');
            $isMobile = (bool) preg_match('/Mobile|Android.*Mobile|iPhone|iPod/i', $userAgent);
            $isTablet = !$isMobile && (bool) preg_match('/iPad|Android|Tablet/i', $userAgent);

            if ($deviceCondition === 'mobile' && !$isMobile) {
                return true;
            }
            if ($deviceCondition === 'tablet' && !$isTablet) {
                return true;
            }
            if ($deviceCondition === 'desktop' && ( $isMobile || $isTablet )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Recompact grid positions after filtering out hidden blocks.
     * Recalculates Y positions to eliminate vertical gaps.
     *
     * @param array $blocks Array of blocks with decoded gridstack data
     * @return array Recompacted blocks
     */
    private function recompactBlocks(array $blocks): array
    {
        if (empty($blocks)) {
            return $blocks;
        }

        // Sort by y then x
        usort($blocks, static function ($a, $b) {
            $aGs = json_decode($a->gridstack, true) ?? [];
            $bGs = json_decode($b->gridstack, true) ?? [];
            $cmp = ( $aGs['y'] ?? 0 ) <=> ( $bGs['y'] ?? 0 );

            return $cmp !== 0 ? $cmp : ( $aGs['x'] ?? 0 ) <=> ( $bGs['x'] ?? 0 );
        });

        // Simple row-based compaction: track the next available Y per column
        $columnHeights = array_fill(0, 12, 0);

        foreach ($blocks as $block) {
            $gs = json_decode($block->gridstack, true) ?? [];
            $x = $gs['x'] ?? 0;
            $w = $gs['w'] ?? 12;
            $h = $gs['h'] ?? 1;

            // Find the maximum height in the columns this widget spans
            $maxY = 0;
            for ($col = $x; $col < min($x + $w, 12); $col++) {
                $maxY = max($maxY, $columnHeights[$col]);
            }

            // Update the gridstack y position
            $gs['y'] = $maxY;
            $block->gridstack = json_encode($gs);

            // Update column heights
            for ($col = $x; $col < min($x + $w, 12); $col++) {
                $columnHeights[$col] = $maxY + $h;
            }
        }

        return $blocks;
    }

    /**
     * Checks whether a global block is excluded for the given path.
     */
    private function isBlockExcludedForPath(GlobalPageBlock $block, string $currentPath): bool
    {
        $raw = $block->getExcludedPaths();
        if (!$raw) {
            return false;
        }

        $patterns = json_decode($raw, true);
        if (!is_array($patterns)) {
            return false;
        }

        foreach ($patterns as $pattern) {
            $pattern = trim($pattern);
            if ($pattern !== '' && $this->pathMatchesPattern($currentPath, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Matches a URL path against a wildcard pattern.
     *
     * Supported syntax:
     *  - Exact: /about
     *  - Single-segment wildcard: /user/* (matches /user/123, not /user/123/profile)
     *  - Multi-segment wildcard: /user/** (matches /user/123/profile)
     *  - Single-char wildcard: /page-? (matches /page-1, /page-a)
     */
    private function pathMatchesPattern(string $path, string $pattern): bool
    {
        // Exact match shortcut
        if ($pattern === $path) {
            return true;
        }

        // Convert pattern to regex:
        // ** → matches any characters including /
        // *  → matches any characters except /
        // ?  → matches exactly one character (not /)
        $quoted = preg_quote($pattern, '#');
        $regex = str_replace(['\*\*', '\*', '\?'], ['.*', '[^/]*', '[^/]'], $quoted);

        return (bool) preg_match('#^' . $regex . '$#', $path);
    }
}
