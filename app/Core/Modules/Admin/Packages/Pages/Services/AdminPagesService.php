<?php

namespace Flute\Admin\Packages\Pages\Services;

use Flute\Core\Database\Entities\Page;
use Flute\Core\Database\Entities\PageBlock;

class AdminPagesService
{
    /**
     * Save page (create or update)
     */
    public function savePage(?Page $page, array $data): Page
    {
        if (!$page) {
            $page = new Page();
        }

        $page->route = $data['route'];
        $page->title = $data['title'];
        $page->description = $data['description'] ?? null;
        $page->keywords = $data['keywords'] ?? null;
        $page->robots = $data['robots'] ?? null;
        $page->og_image = $data['og_image'] ?? null;

        $page->save();

        return $page;
    }

    /**
     * Delete page with all its blocks
     */
    public function deletePage(Page $page): void
    {
        $entities = $page->blocks;
        $entities[] = $page;
        transaction($entities, 'delete')->run();
    }

    /**
     * Save page block (create or update)
     */
    public function savePageBlock(?PageBlock $block, Page $page, array $data): PageBlock
    {
        if (!$block) {
            $block = new PageBlock();
            $block->page = $page;
        }

        $block->widget = $data['widget'];
        $block->gridstack = $data['gridstack'] ?: '{}';
        $block->settings = $data['settings'] ?: '{}';

        $block->save();

        return $block;
    }

    /**
     * Delete page block
     */
    public function deletePageBlock(PageBlock $block): void
    {
        $block->delete();
    }

    /**
     * Validate JSON string
     */
    public function validateJson(string $json): bool
    {
        if (empty($json)) {
            return true;
        }

        json_decode($json);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Get page statistics
     */
    public function getPageStatistics(): array
    {
        $totalPages = Page::query()->count();
        $totalBlocks = PageBlock::query()->count();

        $q = PageBlock::query()->buildQuery();
        $q->columns([new \Cycle\Database\Injection\Expression('COUNT(DISTINCT page_id) AS cnt')]);
        $pagesWithBlocksCount = (int) ($q->fetchAll()[0]['cnt'] ?? 0);

        return [
            'total_pages' => $totalPages,
            'total_blocks' => $totalBlocks,
            'pages_with_blocks' => $pagesWithBlocksCount,
            'pages_without_blocks' => $totalPages - $pagesWithBlocksCount,
        ];
    }

    /**
     * Get most used widgets
     */
    public function getMostUsedWidgets(): array
    {
        $q = PageBlock::query()->buildQuery();
        $q->columns([
            'widget',
            new \Cycle\Database\Injection\Fragment('COUNT(*) AS cnt'),
        ]);
        $q->groupBy('widget');
        $q->orderBy('cnt', 'DESC');
        $q->limit(10);

        $rows = $q->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $out[$row['widget']] = (int) $row['cnt'];
        }

        return $out;
    }

    /**
     * Duplicate page
     */
    public function duplicatePage(Page $originalPage, string $newRoute, string $newTitle): Page
    {
        $newPage = new Page();
        $newPage->route = $newRoute;
        $newPage->title = $newTitle;
        $newPage->description = $originalPage->description;
        $newPage->keywords = $originalPage->keywords;
        $newPage->robots = $originalPage->robots;
        $newPage->og_image = $originalPage->og_image;

        $newPage->save();

        // Копируем блоки
        foreach ($originalPage->blocks as $originalBlock) {
            $newBlock = new PageBlock();
            $newBlock->page = $newPage;
            $newBlock->widget = $originalBlock->widget;
            $newBlock->gridstack = $originalBlock->gridstack;
            $newBlock->settings = $originalBlock->settings;
            $newBlock->save();
        }

        return $newPage;
    }
}
