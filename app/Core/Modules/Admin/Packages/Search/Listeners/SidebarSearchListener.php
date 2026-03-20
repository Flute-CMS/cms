<?php

namespace Flute\Admin\Packages\Search\Listeners;

use Flute\Admin\Packages\Search\Events\AdminSearchEvent;
use Flute\Admin\Packages\Search\Services\AdminSearchResult;

class SidebarSearchListener
{
    public static function handle(AdminSearchEvent $event)
    {
        $menuItems = app(\Flute\Admin\AdminPanel::class)->getAllMenuItems();
        $searchValue = $event->getValue();

        if (empty($searchValue)) {
            return;
        }

        if (str_starts_with($searchValue, '/')) {
            return;
        }

        if (mb_strlen($searchValue, 'UTF-8') < 2) {
            return;
        }

        $searchValueLower = mb_strtolower($searchValue, 'UTF-8');

        self::searchInMenuItems($menuItems, $searchValueLower, $event);
    }

    private static function searchInMenuItems(array $menuItems, string $searchValue, AdminSearchEvent $event)
    {
        foreach ($menuItems as $item) {
            if (!empty($item['items'])) {
                self::searchInMenuItems($item['items'], $searchValue, $event);
            }

            if (!empty($item['children'])) {
                self::searchInMenuItems($item['children'], $searchValue, $event);
            }

            if (empty($item['url']) || $item['url'] === '#') {
                continue;
            }

            if (!empty($item['title'])) {
                $translatedTitle = __($item['title']);
                $itemTitleLower = mb_strtolower($translatedTitle, 'UTF-8');
                $pos = mb_strpos($itemTitleLower, $searchValue);

                if ($pos !== false) {
                    $relevance = 5;
                    if ($itemTitleLower === $searchValue) {
                        $relevance = 10;
                    } elseif ($pos === 0) {
                        $relevance = 8;
                    }

                    $searchResult = new AdminSearchResult(
                        $translatedTitle,
                        $item['url'],
                        $item['icon'] ?? null,
                        __('search.category_navigation'),
                        $relevance,
                    );
                    $event->add($searchResult);
                }
            }
        }
    }
}
