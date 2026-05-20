<?php

namespace Flute\Core\Modules\Page\Services\Concerns;

use Flute\Core\Database\Entities\GlobalPageBlock;
use Nette\Utils\Json;
use RuntimeException;
use Throwable;

trait HandlesGlobalBlocks
{
    public function getGlobalBlocks(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        try {
            $cached = cache()->callback(
                self::GLOBAL_LAYOUT_CACHE_KEY,
                static fn() => GlobalPageBlock::query()->orderBy('sortOrder', 'ASC')->fetchAll(),
                is_performance() ? self::GLOBAL_LAYOUT_CACHE_TIME : 30,
            );

            return $cached;
        } catch (Throwable $e) {
            $this->logger->error('Failed to load global blocks: ' . $e->getMessage());

            return [];
        }
    }

    public function getGlobalLayout(): array
    {
        $layout = [];
        $globalBlocks = $this->getGlobalBlocks();

        foreach ($globalBlocks as $block) {
            try {
                $settings = json_decode($block->getSettings(), true) ?? [];
                $widgetName = $block->getWidget();

                $widgetContent = $this->renderWidgetCached($widgetName, $settings);

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
            } catch (Throwable $e) {
                $this->logger->error('Failed to retrieve global layout widget: ' . $e->getMessage());
            }
        }

        return $layout;
    }

    public function saveGlobalLayout(array $layout): void
    {
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

        usort($layout, static function ($a, $b) {
            $ay = $a['gridstack']['y'] ?? 0;
            $by = $b['gridstack']['y'] ?? 0;
            $cmp = $ay <=> $by;

            return $cmp !== 0 ? $cmp : ( $a['gridstack']['x'] ?? 0 ) <=> ( $b['gridstack']['x'] ?? 0 );
        });

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

        cache()->delete(self::GLOBAL_LAYOUT_CACHE_KEY);
        $this->clearRenderedPageCache();
    }

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

        $visibleBlocks = array_filter($globalBlocks, function ($block) use ($currentPath) {
            if ($this->isBlockExcludedForPath($block, $currentPath)) {
                return false;
            }

            return !( $block->getWidget() !== 'Content' && $this->isBlockHiddenByConditions($block) );
        });

        $visibleBlocks = $this->recompactBlocks(array_values($visibleBlocks));

        foreach ($visibleBlocks as $block) {
            $style = $this->getBlockGridStyle($block);
            $widgetName = $block->getWidget();

            if ($widgetName === 'Content') {
                $this->globalContentRendered = true;

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

    protected function safeRenderGlobalBlock(GlobalPageBlock $block): ?string
    {
        try {
            $settings = json_decode($block->getSettings(), true) ?? [];
            $widgetName = $block->getWidget();

            $content = $this->renderWidgetCached($widgetName, $settings);

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

    private function pathMatchesPattern(string $path, string $pattern): bool
    {
        if ($pattern === $path) {
            return true;
        }

        $quoted = preg_quote($pattern, '#');
        $regex = str_replace(['\*\*', '\*', '\?'], ['.*', '[^/]*', '[^/]'], $quoted);

        return (bool) preg_match('#^' . $regex . '$#', $path);
    }
}
