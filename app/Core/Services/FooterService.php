<?php

namespace Flute\Core\Services;

use Flute\Core\Database\Entities\FooterItem;
use Nette\Utils\Validators;

class FooterService
{
    protected array $cachedItems;
    protected bool $performance;
    protected const CACHE_TIME = 24 * 60 * 60;
    public const CACHE_KEY = 'flute.footer.items';

    /**
     * Constructor method
     * Initializes the format class and sets the default footer items
     */
    public function __construct()
    {
        $this->performance = (bool) (is_performance());

        $this->cachedItems = $this->performance ? cache()->callback(self::CACHE_KEY, fn () => $this->getDefaultItems(), self::CACHE_TIME) : $this->getDefaultItems();
    }

    /**
     * Adds a footer item to the cached items, if the user has access to it
     *
     * @param FooterItem $item The item to add
     *
     * @return self
     */
    public function add(FooterItem $item): self
    {
        $this->cachedItems[] = $this->format($item);

        return $this;
    }

    /**
     * @return FooterSocialService
     */
    public function socials()
    {
        return app(FooterSocialService::class);
    }

    /**
     * Returns all cached footer items
     *
     * @return array
     */
    public function all(): array
    {
        return $this->cachedItems;
    }

    /**
     * Sets the default footer items by fetching them from the database
     */
    protected function getDefaultItems(): array
    {
        $footerItems = $this->getAllFooterItems();
        $tree = $this->buildTree($footerItems);

        $formattedItems = [];
        foreach ($tree as $item) {
            $formattedItems[] = $this->format($item);
        }

        usort($formattedItems, fn ($a, $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

        return $formattedItems;
    }

    protected function getAllFooterItems(): array
    {
        $footerItems = FooterItem::query()
            ->load(['children', 'children.children', 'parent'])
            ->orderBy('position', 'asc')
            ->where([
                'parent_id' => null,
            ])->fetchAll();

        return $footerItems;
    }

    protected function buildTree(array $items, ?int $parentId = null): array
    {
        $tree = [];

        foreach ($items as $item) {
            if ($item->parent && $item->parent->id === $parentId) {
                $item->children = $this->buildTree($items, $item->id);
                $tree[] = $item;
            } else {
                $tree[] = $item;
            }
        }

        usort($tree, fn ($a, $b) => ($a->position ?? 0) <=> ($b->position ?? 0));

        return $tree;
    }

    public function format(FooterItem $FooterItem): array
    {
        $result = [
            'id' => $FooterItem->id,
            'title' => $FooterItem->title,
            'icon' => $FooterItem->icon,
            'url' => $FooterItem->url ? $this->formatUrl($FooterItem->url) : null,
            'new_tab' => $FooterItem->new_tab,
            'position' => $FooterItem->position,
            'children' => [],
            'roles' => [],
        ];

        foreach ($FooterItem->children as $child) {
            $result['children'][] = $this->format($child);
        }

        if ($result['children']) {
            usort($result['children'], function ($a, $b) {
                return $a['position'] <=> $b['position'];
            });
        }

        return $result;
    }

    /**
     * Format a URL for rendering.
     *
     * @param string $url The URL to format.
     * @return string The formatted URL.
     */
    protected function formatUrl(string $url): string
    {
        return Validators::isUrl($url) ? $url : url($url);
    }
}
