<?php

namespace Flute\Core\Modules\Page\Services\Concerns;

use Flute\Core\Database\Entities\Page;
use Flute\Core\Database\Entities\PageBlock;
use Nette\Utils\Json;
use RuntimeException;
use Throwable;

trait HandlesPageLayout
{
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
                    'content' => $this->widgetManager->getWidget('Content')?->render([]) ?? '',
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

                        $widgetContent = $this->renderWidgetCached($widgetName, $settings);

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
                    } catch (Throwable $e) {
                        $this->logger->error("Failed to retrieve layout for path {$path}: " . $e->getMessage());
                        logs()->error($e);
                    }
                }
            }

            return $layout;
        } catch (Throwable $e) {
            $this->logger->error("Failed to retrieve layout for path {$path}: " . $e->getMessage());

            throw $e;
        }
    }

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

    public function saveLayoutForPath(string $path, array $layout): void
    {
        $page = Page::findOne(['route' => $path]);
        if (!$page) {
            $page = new Page();
            $page->setRoute($path);
            $page->setTitle(config('app.name'));
        }

        $page->removeAllBlocks();

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
                    'w' => $item['gridstack']['w'] ?? ( $widget ? $widget->getDefaultWidth() : 12 ),
                    'x' => $item['gridstack']['x'] ?? '',
                    'y' => $item['gridstack']['y'] ?? '',
                    'minW' => $item['gridstack']['minW'] ?? ( $widget ? $widget->getMinWidth() : 4 ),
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
        $this->clearRenderedPageCache();
    }

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
}
