<?php

namespace Flute\Core\Modules\Page\Services\Concerns;

use Exception;
use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Database\Entities\Page;
use Flute\Core\Database\Entities\PageBlock;
use Flute\Core\Modules\Page\Services\WidgetRenderTiming;
use Throwable;

trait HandlesPageRendering
{
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
                $widgetInstance = $this->widgetManager->getWidget($widgetName);

                if ($widgetInstance === null) {
                    continue;
                }

                $startTime = microtime(true);
                $content .= $widgetInstance->render(json_decode($widget->getSettings(), true));
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

    public function renderWidget(int $widgetId)
    {
        $widgetDb = null;

        try {
            $widgetDb = PageBlock::findByPK($widgetId);

            if (!$widgetDb) {
                throw new Exception('Widget ' . $widgetId . ' does not exist on current page');
            }

            $widgetName = $widgetDb->getWidget();
            $widgetInstance = $this->widgetManager->getWidget($widgetName);

            if ($widgetInstance === null) {
                return null;
            }

            $startTime = microtime(true);
            $content = $widgetInstance->render(json_decode($widgetDb->getSettings(), true));
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

        if ($this->canUseRenderedPageCache()) {
            $cached = $this->readRenderedPageCache();
            if ($cached !== null) {
                return $cached;
            }
        }

        $globalBlocks = $this->getGlobalBlocks();

        if (!empty($globalBlocks)) {
            $content = $this->renderGlobalLayout($globalBlocks);
            $this->writeRenderedPageCache($content);

            return $content;
        }

        if (!$this->currentPage) {
            return '';
        }

        $content = $this->renderBlocksAsGrid($this->currentPage->getBlocks(), false);
        $this->writeRenderedPageCache($content);

        return $content;
    }

    public function renderPageContent(Page $page)
    {
        $this->currentPage = $page;
        $this->loadPermissions();

        if (!is_cli()) {
            template()->addGlobal('page', $this->currentPage);
        }

        return response()->view('flute::pages.home');
    }

    protected function renderBlocksAsGrid(array $blocks, bool $isGlobal = false): string
    {
        $content = '';

        $blocks = array_filter(
            $blocks,
            fn($block) => $block->getWidget() === 'Content' || !$this->isBlockHiddenByConditions($block),
        );

        $blocks = $this->recompactBlocks(array_values($blocks));

        $rowMap = $this->buildGridRowMap($blocks);

        foreach ($blocks as $block) {
            $style = $this->getBlockGridStyle($block, $rowMap);

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

    protected function renderWidgetCached(string $widgetName, array $settings): ?string
    {
        $widget = $this->widgetManager->getWidget($widgetName);

        if ($widget === null) {
            return null;
        }

        $cacheTime = method_exists($widget, 'getCacheTime') ? $widget->getCacheTime() : 0;

        if ($cacheTime > 0) {
            $cacheKey = 'widget.html.' . $widgetName . '.' . app()->getLang() . '.' . md5(json_encode($settings));

            return cache()->callback(
                $cacheKey,
                static function () use ($widget, $widgetName, $settings) {
                    $startTime = microtime(true);
                    $html = $widget->render($settings);
                    WidgetRenderTiming::add($widgetName, microtime(true) - $startTime);

                    return $html;
                },
                $cacheTime,
            );
        }

        $startTime = microtime(true);
        $html = $widget->render($settings);
        WidgetRenderTiming::add($widgetName, microtime(true) - $startTime);

        return $html;
    }

    protected function safeRenderBlock(PageBlock $block): ?string
    {
        try {
            $settings = json_decode($block->getSettings(), true) ?? [];
            $widgetName = $block->getWidget();

            $content = $this->renderWidgetCached($widgetName, $settings);

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

    protected function getBlockGridStyle($block, array $rowMap = []): string
    {
        $gridstack = json_decode($block->gridstack, true) ?? [];

        $col = ( $gridstack['x'] ?? 0 ) + 1;
        $colSpan = $gridstack['w'] ?? 1;
        $style = sprintf('grid-column: %d / span %d;', $col, $colSpan);

        if (!empty($rowMap)) {
            $y = $gridstack['y'] ?? 0;
            $h = $gridstack['h'] ?? 1;

            $startRow = $rowMap[$y] ?? 1;

            $endY = $y + $h;
            $rowSpan = 1;
            foreach ($rowMap as $gy => $gr) {
                if ($gy > $y && $gy < $endY) {
                    $rowSpan = $gr - $startRow + 1;
                }
            }

            $style .= $rowSpan > 1
                ? sprintf(' grid-row: %d / span %d;', $startRow, $rowSpan)
                : sprintf(' grid-row: %d;', $startRow);
        }

        return $style;
    }

    private function buildGridRowMap(array $blocks): array
    {
        $yValues = [];
        foreach ($blocks as $block) {
            $gs = json_decode($block->gridstack, true) ?? [];
            $yValues[] = $gs['y'] ?? 0;
        }

        $yValues = array_unique($yValues);
        sort($yValues);

        $map = [];
        $row = 1;
        foreach ($yValues as $y) {
            $map[$y] = $row++;
        }

        return $map;
    }

    private function hasPushContent(): bool
    {
        try {
            $pushContent = view()->yieldPushContent('content');

            return !empty(trim(strip_tags($pushContent)));
        } catch (Throwable) {
            return true;
        }
    }

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

        $rolesCondition = $conditions['roles'] ?? [];
        if (!empty($rolesCondition) && is_array($rolesCondition)) {
            if (!$this->userService->isLoggedIn()) {
                return true;
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

    private function recompactBlocks(array $blocks): array
    {
        if (empty($blocks)) {
            return $blocks;
        }

        usort($blocks, static function ($a, $b) {
            $aGs = json_decode($a->gridstack, true) ?? [];
            $bGs = json_decode($b->gridstack, true) ?? [];
            $cmp = ( $aGs['y'] ?? 0 ) <=> ( $bGs['y'] ?? 0 );

            return $cmp !== 0 ? $cmp : ( $aGs['x'] ?? 0 ) <=> ( $bGs['x'] ?? 0 );
        });

        $columnHeights = array_fill(0, 12, 0);

        foreach ($blocks as $block) {
            $gs = json_decode($block->gridstack, true) ?? [];
            $x = $gs['x'] ?? 0;
            $w = $gs['w'] ?? 12;
            $h = $gs['h'] ?? 1;

            $maxY = 0;
            for ($col = $x; $col < min($x + $w, 12); $col++) {
                $maxY = max($maxY, $columnHeights[$col]);
            }

            $gs['y'] = $maxY;
            $block->gridstack = json_encode($gs);

            for ($col = $x; $col < min($x + $w, 12); $col++) {
                $columnHeights[$col] = $maxY + $h;
            }
        }

        return $blocks;
    }
}
