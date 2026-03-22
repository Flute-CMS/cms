<?php

namespace Flute\Core\Services;

use Flute\Core\Database\Entities\FooterItem;
use Nette\Utils\Validators;

class FooterService
{
    public const CACHE_KEY = 'flute.footer.items';

    public const CACHE_TAG = 'footer';

    protected const CACHE_TIME = 24 * 60 * 60;

    protected ?array $cachedItems = null;

    protected bool $performance;

    /**
     * Constructor method
     * Initializes the format class; DB/cache loading is deferred to first access.
     */
    public function __construct()
    {
        $this->performance = (bool) is_performance();
    }

    /**
     * Lazily loads footer items from cache/DB on first access.
     */
    protected function loadItems(): array
    {
        if ($this->cachedItems !== null) {
            return $this->cachedItems;
        }

        cache()->tagKey(self::CACHE_TAG, self::CACHE_KEY);
        $this->cachedItems = cache()->callback(self::CACHE_KEY, fn() => $this->getDefaultItems(), self::CACHE_TIME);

        return $this->cachedItems;
    }

    /**
     * Adds a footer item to the cached items, if the user has access to it
     *
     * @param FooterItem $item The item to add
     */
    public function add(FooterItem $item): self
    {
        $this->loadItems();
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
     */
    public function all(): array
    {
        return $this->loadItems();
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
            usort($result['children'], static fn($a, $b) => $a['position'] <=> $b['position']);
        }

        return $result;
    }

    /**
     * Sets the default footer items by fetching them from the database
     */
    protected function getDefaultItems(): array
    {
        $footerItems = FooterItem::query()
            ->load(['children', 'children.children'])
            ->orderBy('position', 'asc')
            ->where(['parent_id' => null])
            ->fetchAll();

        $formattedItems = [];
        foreach ($footerItems as $item) {
            $formattedItems[] = $this->format($item);
        }

        return $formattedItems;
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
