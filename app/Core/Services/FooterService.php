<?php

namespace Flute\Core\Services;

use Flute\Core\App;
use Flute\Core\Database\Entities\FooterItem;
use Nette\Utils\Validators;

class FooterService
{
    protected array $cachedItems; // Array to hold footer items
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

        $this->cachedItems = $this->performance ? cache()->callback(self::CACHE_KEY, function () {
            return $this->getDefaultItems();
        }, self::CACHE_TIME) : $this->getDefaultItems();
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
        $FooterItems = $this->getAllFooterItems();

        $formattedItems = [];

        foreach ($FooterItems as $item) {
            $format = $this->format($item);
            $formattedItems[] = $format;
        }


        return $formattedItems;
    }

    protected function getAllFooterItems(): array
    {
        // Eager load all children recursively to avoid N+1 queries
        return FooterItem::query()
            ->load(['children', 'children.children'])
            ->orderBy('position', 'asc')
            ->where([
                'parent_id' => null,
            ])->fetchAll();
    }

    public function format(FooterItem $FooterItem): array
    {
        $result = [
            'id' => $FooterItem->id,
            'title' => $FooterItem->title,
            'url' => $this->formatUrl($FooterItem->url),
            'new_tab' => $FooterItem->new_tab,
            'position' => $FooterItem->position,
            'children' => [],
            'roles' => []
        ];

        foreach ($FooterItem->children as $child) {
            $result['children'][] = $this->format($child);
        }

        if ($result['children'])
            usort($result['children'], function ($a, $b) {
                return $a['position'] <=> $b['position'];
            });

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