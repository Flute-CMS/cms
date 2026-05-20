<?php

namespace Flute\Core\Modules\Page\Services;

use Flute\Core\Database\Entities\Page;
use Flute\Core\Modules\Page\Services\Concerns\HandlesGlobalBlocks;
use Flute\Core\Modules\Page\Services\Concerns\HandlesPageCache;
use Flute\Core\Modules\Page\Services\Concerns\HandlesPageLayout;
use Flute\Core\Modules\Page\Services\Concerns\HandlesPageRendering;
use Flute\Core\Modules\Page\Services\Concerns\HandlesPageRouting;
use Flute\Core\Router\Contracts\RouterInterface;
use Flute\Core\Services\UserService;
use Flute\Core\Support\FluteRequest;
use Psr\Log\LoggerInterface;
use Throwable;

class PageManager
{
    use HandlesGlobalBlocks;
    use HandlesPageCache;
    use HandlesPageLayout;
    use HandlesPageRendering;
    use HandlesPageRouting;

    protected const PAGE_CACHE_TIME = 3600;

    protected const PAGES_CACHE_KEY = 'flute.pages.all';

    protected const PAGES_CACHE_TIME = 3600;

    protected const GLOBAL_LAYOUT_CACHE_KEY = 'flute.global.layout';

    protected const GLOBAL_LAYOUT_CACHE_TIME = 3600;

    protected const RENDERED_PAGE_CACHE_TIME = 60;

    protected RouterInterface $router;

    protected bool $disabled = false;

    private bool $globalContentRendered = false;

    private static bool $pagesLoaded = false;

    private bool $pagesDatabaseUnavailable = false;

    private bool $pagesDatabaseFailureLogged = false;

    private array $permissions = [];

    private ?Page $currentPage = null;

    private FluteRequest $request;

    private UserService $userService;

    private LoggerInterface $logger;

    private WidgetManager $widgetManager;

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

    public function loadCurrentPage(): void
    {
        if ($this->pagesDatabaseUnavailable) {
            return;
        }

        if (!is_cli() && !is_admin_path()) {
            try {
                $routePath = $this->request->getPathInfo();
                $cacheKey = 'flute.page.route.' . md5($routePath);

                $pageId = cache()->callback(
                    $cacheKey,
                    static function () use ($routePath) {
                        $page = Page::findOne(['route' => $routePath]);

                        return $page ? $page->id : null;
                    },
                    is_performance() ? self::PAGE_CACHE_TIME : 30,
                );

                $this->currentPage = $pageId ? Page::findByPK($pageId) : Page::findOne(['route' => $routePath]);

                if ($this->currentPage) {
                    $this->loadPermissions();

                    template()->addGlobal('page', $this->currentPage);
                }
            } catch (Throwable $e) {
                $this->handlePagesDatabaseFailure($e, 'current page lookup');
            }
        }
    }

    public function isGlobalContentRendered(): bool
    {
        return $this->globalContentRendered;
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

    public function getCurrentPage(): ?Page
    {
        return $this->currentPage;
    }

    public function getBlocks(): array
    {
        return $this->currentPage ? $this->currentPage->getBlocks() : [];
    }

    public function hasAnyBlocks(): bool
    {
        if ($this->currentPage && !empty($this->currentPage->getBlocks())) {
            return true;
        }

        return !empty($this->getGlobalBlocks());
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function hasAccessToEdit(): bool
    {
        return $this->userService->isLoggedIn() && $this->userService->can('admin.pages');
    }

    public function isEditMode(): bool
    {
        $editMode = $this->request->query->get('editMode') ?? null;

        return $this->hasAccessToEdit() && $editMode;
    }

    public function __get($name)
    {
        if ($this->currentPage && property_exists($this->currentPage, $name)) {
            return $this->currentPage->$name;
        }

        return null;
    }

    protected function loadPermissions(): void
    {
        try {
            foreach ($this->currentPage->getPermissions() as $permission) {
                $this->permissions[] = $permission;
            }
        } catch (Throwable $e) {
            $this->handlePagesDatabaseFailure($e, 'page permission lookup');
        }
    }
}
